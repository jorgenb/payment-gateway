<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Adyen\AdyenException;
use Adyen\Model\Checkout\Amount;
use Adyen\Model\Checkout\CreateCheckoutSessionRequest;
use Adyen\Model\Checkout\PaymentCaptureRequest;
use Adyen\Model\Checkout\PaymentRefundRequest;
use Adyen\Model\Checkout\StandalonePaymentCancelRequest;
use Adyen\Service\Checkout\PaymentsApi;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Support\Str;
use Throwable;

class AdyenPaymentProvider extends BasePaymentProvider
{
    public function __construct(
        private readonly PaymentProviderConfigResolverInterface $configResolver,
    ) {}

    /**
     * @throws AdyenException
     */
    private function makeAdyenClient(string $apiKey, string $environment): \Adyen\Client
    {
        $client = new \Adyen\Client;
        $client->setXApiKey($apiKey);
        $client->setEnvironment($environment); // 'test' or 'live'

        return $client;
    }

    /**
     * Initiates a payment with the specified provider.
     *
     * @return PaymentResponse The response containing the payment details.
     *
     * @throws AdyenException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function initiate(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::PENDING);

        $adyenConfig = $this->configResolver->resolve(PaymentProvider::ADYEN);
        $client = $this->makeAdyenClient($adyenConfig->apiKey, $adyenConfig->environment ?? 'test');
        $paymentsApi = new PaymentsApi($client);

        $amount = new Amount;
        $amount->setCurrency($payment->currency)
            ->setValue($payment->amount_minor);

        $request = new CreateCheckoutSessionRequest;
        $request->setReference(Str::upper($payment->id))
            ->setAmount($amount)
            ->setMerchantAccount($adyenConfig->merchantAccount)
            ->setCountryCode('NO') // TODO: Make this dynamic. FE should send country code or get from tenant config?
            ->setReturnUrl($adyenConfig->redirectUrl);

        $captureDelay = $payment->getCaptureConfigurationForProvider();
        $request->setCaptureDelayHours($captureDelay);

        $idempotencyKey = $payment->id;
        $response = $paymentsApi->sessions($request, ['idempotencyKey' => $idempotencyKey]);

        $payment->update([
            'metadata' => [
                'sessionId' => $response->getId(),
                'sessionData' => $response->getSessionData(),
            ],
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::INITIATED,
            payload: $response->toArray()
        );

        return new PaymentResponse(
            status: PaymentStatus::INITIATED,
            payment: $payment,
            responseData: $response->toArray(),
            metadata: $payment->metadata,
        );
    }

    /**
     * Charges a previously reserved payment.
     *
     * This method captures the specified amount from a reserved payment.
     * It should support idempotency to prevent duplicate charges.
     *
     * @return PaymentResponse The response containing the charge status and details.
     *
     * @throws Throwable
     */
    public function charge(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $adyenConfig = $this->configResolver->resolve(PaymentProvider::ADYEN);
        $client = $this->makeAdyenClient($adyenConfig->apiKey, $adyenConfig->environment ?? 'test');
        $modificationsApi = new \Adyen\Service\Checkout\ModificationsApi($client);

        try {
            $captureRequest = new PaymentCaptureRequest([
                'amount' => [
                    'currency' => $payment->currency,
                    'value' => $payment->amount_minor,
                ],
                'merchantAccount' => $adyenConfig->merchantAccount,
                'reference' => $payment->id,
            ]);

            $response = $modificationsApi->captureAuthorisedPayment($payment->external_id, $captureRequest);

            $status = PaymentStatus::CHARGED;
            $payment->update([
                'status' => $status,
            ]);

            $this->recordPaymentEvent(
                payment: $payment,
                newStatus: $status,
                payload: $response->toArray()
            );

            return new PaymentResponse(
                status: $status,
                payment: $payment,
                responseData: $response->toArray()
            );
        } catch (Throwable $e) {
            $this->recordPaymentEvent(
                payment: $payment,
                newStatus: PaymentStatus::FAILED,
                payload: ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    /**
     * Refunds a previously settled transaction (a charged payment).
     * The refunded amount will be transferred back to
     * the customer's account.
     *
     * This can be a full or partial refund depending on provider support.
     *
     * @return RefundResponse The response containing the refund status.
     *
     * @throws Throwable
     */
    public function refund(PaymentRefund $refund): RefundResponse
    {
        $this->ensureStatus($refund->payment, PaymentStatus::CHARGED);

        $adyenConfig = $this->configResolver->resolve(PaymentProvider::ADYEN);
        $client = $this->makeAdyenClient($adyenConfig->apiKey, $adyenConfig->environment ?? 'test');
        $modificationsApi = new \Adyen\Service\Checkout\ModificationsApi($client);

        try {
            $refundRequest = new PaymentRefundRequest([
                'amount' => [
                    'currency' => $refund->currency,
                    'value' => $refund->amount_minor,
                ],
                'merchantAccount' => $adyenConfig->merchantAccount,
                'reference' => $refund->payment_id,
            ]);

            $refundResponse = $modificationsApi->refundCapturedPayment(
                $refund->payment->external_id,
                $refundRequest,
                ['idempotencyKey' => $refund->id]
            );

            $status = PaymentStatus::REFUND_INITIATED;

            $refund->update([
                'external_refund_id' => $refundResponse->getPspReference(),
                'status' => $status,
            ]);

            $this->recordRefundEvent(
                refund: $refund,
                newStatus: $status,
                payload: $refundResponse->toArray(),
            );

            return new RefundResponse(
                status: $status,
                refund: $refund,
                responseData: $refundResponse->toArray()
            );
        } catch (Throwable $e) {
            $this->recordRefundEvent(
                refund: $refund,
                newStatus: PaymentStatus::REFUND_FAILED,
                payload: ['error' => $e->getMessage()],
            );
            throw $e;
        }
    }

    /**
     * Cancels a reserved payment before it is charged.
     *
     * Some providers only allow full cancellation.
     *
     * @return PaymentResponse The response containing the cancel status.
     *
     * @throws Throwable
     */
    public function cancel(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $adyenConfig = $this->configResolver->resolve(PaymentProvider::ADYEN);
        $client = $this->makeAdyenClient($adyenConfig->apiKey, $adyenConfig->environment ?? 'test');
        $modificationsApi = new \Adyen\Service\Checkout\ModificationsApi($client);

        try {
            $cancelRequest = new StandalonePaymentCancelRequest([
                'paymentReference' => $payment->external_id,
                'merchantAccount' => $adyenConfig->merchantAccount,
                'reference' => $payment->id,
            ]);

            $cancellationResponse = $modificationsApi->cancelAuthorisedPayment($cancelRequest);
        } catch (Throwable $e) {
            $this->recordPaymentEvent(
                payment: $payment,
                newStatus: PaymentStatus::FAILED,
                payload: ['error' => $e->getMessage()]
            );
            throw $e;
        }

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::CANCELLED,
            payload: $cancellationResponse->toArray()
        );

        return new PaymentResponse(
            status: PaymentStatus::CANCELLED,
            payment: $payment,
            responseData: $cancellationResponse->toArray()
        );
    }
}

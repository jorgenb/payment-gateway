<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Adyen\AdyenException;
use Adyen\Model\Checkout\Amount;
use Adyen\Model\Checkout\CreateCheckoutSessionRequest;
use Adyen\Model\Checkout\PaymentCaptureRequest;
use Adyen\Model\Checkout\PaymentRefundRequest;
use Adyen\Model\Checkout\StandalonePaymentCancelRequest;
use Adyen\Service\Checkout\ModificationsApi;
use Adyen\Service\Checkout\PaymentsApi;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Data\WidgetMetadataData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Throwable;

class AdyenPaymentProvider extends BasePaymentProvider
{
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
     */
    public function initiate(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::PENDING);

        $client = $this->makeAdyenClient($config->apiKey, $config->environment ?? 'test');
        $paymentsApi = new PaymentsApi($client);

        $amount = new Amount;
        $amount->setCurrency($payment->currency)
            ->setValue($payment->amount_minor);

        $request = new CreateCheckoutSessionRequest;
        $request->setReference($payment->id)
            ->setAmount($amount)
            ->setMerchantAccount($config->merchantAccount)
            ->setCountryCode('NO') // TODO: Make this dynamic. FE should send country code or get from tenant config?
            ->setReturnUrl($config->redirectUrl)
            ->setMetadata($config->contextId ? ['contextId' => $config->contextId] : []);

        $captureDelay = $payment->getCaptureConfigurationForProvider();
        $request->setCaptureDelayHours($captureDelay);

        $idempotencyKey = $payment->id;
        $response = $paymentsApi->sessions($request, ['idempotencyKey' => $idempotencyKey]);

        $status = PaymentStatus::INITIATED;
        $externalId = $response->getId();
        $sessionData = $response->getSessionData();
        $payment->update([
            'context_id' => $config->contextId,
            'status' => $status,
            'external_id' => $externalId,
            'metadata' => WidgetMetadataData::from([
                'clientKey' => $config->clientKey,
                'sessionId' => $externalId,
                'sessionData' => $sessionData,
            ]),
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            status: $status,
            payload: $response->toArray()
        );

        return new PaymentResponse(
            status: $status,
            payment: $payment,
            responseData: $response->toArray(),
            metadata: WidgetMetadataData::from([
                'clientKey' => $config->clientKey,
                'sessionId' => $externalId,
                'sessionData' => $sessionData,
            ])->toArray()
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
     * @throws AdyenException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function charge(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $client = $this->makeAdyenClient($config->apiKey, $config->environment ?? 'test');
        $modificationsApi = new ModificationsApi($client);

        try {
            $captureRequest = new PaymentCaptureRequest([
                'amount' => [
                    'currency' => $payment->currency,
                    'value' => $payment->amount_minor,
                ],
                'merchantAccount' => $config->merchantAccount,
                'reference' => $payment->id,
            ]);

            $response = $modificationsApi->captureAuthorisedPayment($payment->external_id, $captureRequest);

            $status = PaymentStatus::CHARGED;
            $payment->update([
                'status' => $status,
            ]);

            $this->recordPaymentEvent(
                payment: $payment,
                status: $status,
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
                status: PaymentStatus::FAILED,
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
     * @throws AdyenException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function refund(PaymentRefund $refund, PaymentProviderConfig $config): RefundResponse
    {
        $this->ensureStatus($refund->payment, PaymentStatus::CHARGED);

        $client = $this->makeAdyenClient($config->apiKey, $config->environment ?? 'test');
        $modificationsApi = new ModificationsApi($client);

        try {
            $refundRequest = new PaymentRefundRequest([
                'amount' => [
                    'currency' => $refund->currency,
                    'value' => $refund->amount_minor,
                ],
                'merchantAccount' => $config->merchantAccount,
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
                status: $status,
                payload: $refundResponse->toArray(),
            );

            return new RefundResponse(
                status: $status,
                refund: $refund,
                responseData: $refundResponse->toArray()
            );
        } catch (Throwable $e) {
            $status = PaymentStatus::FAILED;
            $this->recordRefundEvent(
                refund: $refund,
                status: $status,
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
     * @throws AdyenException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function cancel(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $client = $this->makeAdyenClient($config->apiKey, $config->environment ?? 'test');
        $modificationsApi = new ModificationsApi($client);

        try {
            $cancelRequest = new StandalonePaymentCancelRequest([
                'paymentReference' => $payment->external_id,
                'merchantAccount' => $config->merchantAccount,
                'reference' => $payment->id,
            ]);

            $cancellationResponse = $modificationsApi->cancelAuthorisedPayment($cancelRequest);

            $status = PaymentStatus::CANCELLED;
            $payment->update([
                'status' => $status,
            ]);

            $this->recordPaymentEvent(
                payment: $payment,
                status: $status,
                payload: $cancellationResponse->toArray()
            );

            return new PaymentResponse(
                status: $status,
                payment: $payment,
                responseData: $cancellationResponse->toArray()
            );
        } catch (Throwable $e) {

            $status = PaymentStatus::FAILED;
            $this->recordPaymentEvent(
                payment: $payment,
                status: $status,
                payload: ['error' => $e->getMessage()]
            );

            throw $e;
        }

    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Bilberry\PaymentGateway\Connectors\NetsConnector;
use Bilberry\PaymentGateway\Data\NetsPaymentChargeResponseData;
use Bilberry\PaymentGateway\Data\NetsPaymentRefundResponseData;
use Bilberry\PaymentGateway\Data\NetsPaymentResponseData;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Data\WidgetMetadataData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Http\Requests\NetsCancelPaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsChargePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsCreatePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsRefundChargeRequest;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use JsonException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class NetsPaymentProvider extends BasePaymentProvider
{
    private function createConnector(PaymentProviderConfig $config): NetsConnector
    {
        return new NetsConnector(
            apiKey: $config->apiKey,
            merchantAccount: $config->merchantAccount,
        );
    }

    /**
     * Initiates a one-time payment using the Nets API.
     *
     * @throws Throwable
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function initiate(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::PENDING);
        $request = new NetsCreatePaymentRequest($payment, $config);

        $connector = $this->createConnector($config);
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        /** @var NetsPaymentResponseData $responseData */
        $responseData = $response->dto();

        $status = PaymentStatus::INITIATED;
        $payment->update([
            'status' => $status,
            'external_id' => $responseData->paymentId,
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            status: $status,
            payload: $responseData->rawPayload
        );

        return new PaymentResponse(
            status: PaymentStatus::INITIATED,
            payment: $payment,
            responseData: $responseData->rawPayload,
            metadata: WidgetMetadataData::from(['clientKey' => $config->clientKey])->toArray()
        );
    }

    /**
     * Charges a previously reserved payment using the Nets API.
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function charge(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $request = new NetsChargePaymentRequest($payment);

        $connector = $this->createConnector($config);
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        /** @var NetsPaymentChargeResponseData $responseData */
        $responseData = $response->dto();

        $status = PaymentStatus::PROCESSING;
        $payment->update([
            'external_charge_id' => $responseData->chargeId,
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            status: $status,
            payload: $responseData->rawPayload
        );

        return new PaymentResponse(
            status: $status,
            payment: $payment,
            responseData: $responseData->rawPayload
        );
    }

    /**
     * Cancels a reserved payment using the Nets API.
     *
     * @throws JsonException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function cancel(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        // Instantiate the request for canceling a payment.
        $request = new NetsCancelPaymentRequest($payment);

        $connector = $this->createConnector($config);
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        $responseData = $response->json();

        $status = PaymentStatus::PROCESSING;

        $this->recordPaymentEvent(
            payment: $payment,
            status: $status,
            payload: $responseData
        );

        return new PaymentResponse(
            status: $status,
            payment: $payment,
            responseData: $responseData
        );
    }

    /**
     * Refunds a previously settled transaction.
     * Supports both full and partial refunds.
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws Throwable
     * @throws UnknownCurrencyException
     */
    public function refund(PaymentRefund $refund, PaymentProviderConfig $config): RefundResponse
    {
        $this->ensureStatus($refund->payment, PaymentStatus::CHARGED);

        $request = new NetsRefundChargeRequest($refund);
        $request->headers()->add('Idempotency-Key', $refund->id);

        $connector = $this->createConnector($config);

        try {
            $response = $connector->send($request);
        } catch (Throwable $e) {
            $status = PaymentStatus::FAILED;
            $this->recordRefundEvent(
                refund: $refund,
                status: $status,
                payload: [
                    'error' => $e->getMessage(),
                ],
            );
            throw $e;
        }

        $status = PaymentStatus::REFUND_INITIATED;

        /** @var NetsPaymentRefundResponseData $responseData */
        $responseData = $response->dto();
        $refund->update([
            'external_refund_id' => $responseData->refundId,
            'status' => $status,
        ]);

        $this->recordRefundEvent(
            refund: $refund,
            status: $status,
            payload: $responseData->rawPayload,
        );

        return new RefundResponse(
            status: $status,
            refund: $refund,
            responseData: $responseData->rawPayload
        );
    }

    /**
     * @throws Throwable
     */
    private function handlePaymentConnectorRequest(Payment $payment, Request $request, NetsConnector $connector): Response
    {
        $request->headers()->add('Idempotency-Key', $payment->id);

        try {
            return $connector->send($request);
        } catch (Throwable $e) {
            $status = PaymentStatus::FAILED;
            $payment->update(['status' => $status]);
            $this->recordPaymentEvent(
                payment: $payment,
                status: $status,
                payload: [
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }
}

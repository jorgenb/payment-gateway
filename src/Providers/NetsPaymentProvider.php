<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Bilberry\PaymentGateway\Connectors\NetsConnector;
use Bilberry\PaymentGateway\Data\NetsPaymentChargeResponseData;
use Bilberry\PaymentGateway\Data\NetsPaymentRefundResponseData;
use Bilberry\PaymentGateway\Data\NetsPaymentResponseData;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Http\Requests\NetsCancelPaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsChargePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsCreatePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsRefundChargeRequest;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use JsonException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class NetsPaymentProvider extends BasePaymentProvider
{
    private readonly PaymentProviderConfigResolverInterface $configResolver;

    public function __construct(PaymentProviderConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    private function createConnector(): NetsConnector
    {
        $config = $this->configResolver->resolve(PaymentProvider::NETS);

        return new NetsConnector(
            apiKey: $config->apiKey,
            merchantAccount: $config->merchantAccount,
        );
    }

    /**
     * Initiates a one-time payment using the Nets API.
     *
     * @throws Throwable
     */
    public function initiate(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::PENDING);
        $request = new NetsCreatePaymentRequest($payment);

        $connector = $this->createConnector();
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        /** @var NetsPaymentResponseData $responseData */
        $responseData = $response->dto();
        $payment->update([
            'external_id' => $responseData->paymentId,
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::INITIATED,
            payload: $responseData->rawPayload
        );

        return new PaymentResponse(
            status: PaymentStatus::INITIATED,
            payment: $payment,
            responseData: $responseData->rawPayload
        );
    }

    /**
     * Charges a previously reserved payment using the Nets API.
     *
     * @throws Throwable
     */
    public function charge(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        $request = new NetsChargePaymentRequest($payment);

        $connector = $this->createConnector();
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        /** @var NetsPaymentChargeResponseData $responseData */
        $responseData = $response->dto();
        $payment->update([
            'external_charge_id' => $responseData->chargeId,
        ]);

        $status = PaymentStatus::PROCESSING;

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::PROCESSING,
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
     * @throws Throwable
     * @throws JsonException
     */
    public function cancel(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        // Instantiate the request for canceling a payment.
        $request = new NetsCancelPaymentRequest($payment);

        $connector = $this->createConnector();
        $response = $this->handlePaymentConnectorRequest($payment, $request, $connector);

        $responseData = $response->json();

        $status = PaymentStatus::PROCESSING;

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: $status,
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
     * @throws Throwable
     */
    public function refund(PaymentRefund $refund): RefundResponse
    {
        $this->ensureStatus($refund->payment, PaymentStatus::CHARGED);

        $request = new NetsRefundChargeRequest($refund);
        $request->headers()->add('Idempotency-Key', $refund->id);

        $connector = $this->createConnector();

        try {
            $response = $connector->send($request);
        } catch (Throwable $e) {
            $this->recordRefundEvent(
                refund: $refund,
                newStatus: PaymentStatus::REFUND_FAILED,
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
            newStatus: $status,
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
            $this->recordPaymentEvent(
                payment: $payment,
                newStatus: PaymentStatus::FAILED,
                payload: [
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }
}

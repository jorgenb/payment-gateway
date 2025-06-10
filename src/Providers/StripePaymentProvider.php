<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class StripePaymentProvider extends BasePaymentProvider
{
    public function __construct(
        private readonly StripeClient $client,
    ) {
    }

    /**
     * Initiates a payment with the specified provider.
     *
     * @param  Payment  $payment
     * @return PaymentResponse The response containing the payment details.
     * @throws ApiErrorException
     */
    public function initiate(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::PENDING);
        $captureMethod = $payment->capture_at
            ? 'manual'
            : ($payment->auto_capture ?? true ? 'automatic' : 'manual');

        $intentResponse = $this->client->paymentIntents->create([
            'amount'   => $payment->amount_minor,
            'currency' => $payment->currency,
            'metadata' => [
                'merchantReference' => $payment->id,
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'capture_method' => $captureMethod,
        ], [
            'idempotency_key' => $payment->id,
        ]);

        $payment->update([
            'external_id' => $intentResponse->id,
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::INITIATED,
            payload: $intentResponse->toArray()
        );

        return new PaymentResponse(
            status: PaymentStatus::INITIATED,
            payment: $payment,
            responseData: $intentResponse->toArray(),
            metadata: ['clientSecret' => $intentResponse->client_secret]
        );
    }

    /**
     * Charges a previously reserved payment.
     *
     * This method captures the specified amount from a reserved payment.
     * It should support idempotency to prevent duplicate charges.
     *
     * @param  Payment  $payment
     * @return PaymentResponse The response containing the charge status and details.
     * @throws Throwable
     */
    public function charge(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        try {
            $response = $this->client->paymentIntents->capture(
                $payment->external_id,
                [],
                ['idempotency_key' => $payment->id]
            );
        } catch (Throwable $e) {
            $this->recordPaymentEvent(
                payment: $payment,
                newStatus: PaymentStatus::FAILED,
                payload: ['error' => $e->getMessage()]
            );

            throw $e;
        }

        $payment->update([
            'external_charge_id' => $response->latest_charge
        ]);

        $this->recordPaymentEvent(
            payment: $payment,
            newStatus: PaymentStatus::PROCESSING,
            payload: $response->toArray()
        );

        return new PaymentResponse(
            status: PaymentStatus::PROCESSING,
            payment: $payment,
            responseData: $response->toArray()
        );
    }

    /**
     * Refunds a previously settled transaction (a charged payment).
     * The refunded amount will be transferred back to
     * the customer's account.
     *
     * This can be a full or partial refund depending on provider support.
     *
     * @param  PaymentRefund  $refund
     * @throws Throwable
     * @return RefundResponse The response containing the refund status.
     *
     */
    public function refund(PaymentRefund $refund): RefundResponse
    {
        $this->ensureStatus($refund->payment, PaymentStatus::CHARGED);

        try {
            $refundResponse = $this->client->refunds->create([
                'payment_intent' => $refund->payment->external_id,
                'amount'         => $refund->amount_minor,
                'metadata'       => [
                    'refund_id'   => $refund->id,
                    'payment_id'  => $refund->payment->id,
                    'external_id' => $refund->payment->external_id,
                ],
            ], [
                'idempotency_key' => $refund->id,
            ]);
        } catch (Throwable $e) {
            $this->recordRefundEvent(
                refund: $refund,
                newStatus: PaymentStatus::FAILED,
                payload: ['error' => $e->getMessage()],
            );
            throw $e;
        }

        $status = PaymentStatus::REFUND_INITIATED;

        $refund->update([
            'external_refund_id' => $refundResponse->id,
            'status'             => $status
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
    }

    /**
     * Cancels a reserved payment before it is charged.
     *
     * Some providers only allow full cancellation.
     *
     * @param  Payment  $payment
     * @throws Throwable
     * @return PaymentResponse The response containing the cancel status.
     */
    public function cancel(Payment $payment): PaymentResponse
    {
        $this->ensureStatus($payment, PaymentStatus::RESERVED);

        try {
            $cancellationResponse = $this->client->paymentIntents->cancel(
                $payment->external_id,
                [],
                ['idempotency_key' => $payment->id]
            );
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
            newStatus: PaymentStatus::PROCESSING,
            payload: $cancellationResponse->toArray()
        );

        return new PaymentResponse(
            status: PaymentStatus::PROCESSING,
            payment: $payment,
            responseData: $cancellationResponse->toArray()
        );
    }
}

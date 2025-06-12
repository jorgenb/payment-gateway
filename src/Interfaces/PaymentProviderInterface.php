<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;

interface PaymentProviderInterface
{
    /**
     * Initiates a payment with the specified provider.
     *
     * @return PaymentResponse The response containing the payment details.
     */
    public function initiate(Payment $payment): PaymentResponse;

    /**
     * Charges a previously reserved payment.
     *
     * This method captures the specified amount from a reserved payment.
     * It should support idempotency to prevent duplicate charges.
     *
     * @return PaymentResponse The response containing the charge status and details.
     */
    public function charge(Payment $payment): PaymentResponse;

    /**
     * Refunds a previously settled transaction (a charged payment).
     * The refunded amount will be transferred back to
     * the customer's account.
     *
     * This can be a full or partial refund depending on provider support.
     *
     * @return RefundResponse The response containing the refund status.
     */
    public function refund(PaymentRefund $refund): RefundResponse;

    /**
     * Cancels a reserved payment before it is charged.
     *
     * Some providers only allow full cancellation.
     *
     * @return PaymentResponse The response containing the cancel status.
     */
    public function cancel(Payment $payment): PaymentResponse;

    /**
     * Handles callbacks/webhooks from the payment provider.
     *
     * Payment providers send multiple callbacks during a payment lifecycle.
     * This method should handle and map different events appropriately.
     */
    public function handleCallback(PaymentCallbackData $data): void;

    /**
     * Records a payment event and updates the payment status.
     *
     * @param  Payment  $payment  The payment model instance
     * @param  PaymentStatus  $newStatus  New payment status
     * @param  array  $payload  Additional event data
     * @param  PaymentCallbackData|null  $callbackData  Additional callback data
     */
    public function recordPaymentEvent(
        Payment $payment,
        PaymentStatus $newStatus,
        array $payload = [],
        ?PaymentCallbackData $callbackData = null
    ): void;

    /**
     * Records a refund event and updates the refund status.
     *
     * @param  PaymentRefund  $refund  The refund model instance
     * @param  PaymentStatus  $newStatus  New refund status
     * @param  array  $payload  Additional event data
     * @param  PaymentCallbackData|null  $callbackData  Additional callback data
     */
    public function recordRefundEvent(
        PaymentRefund $refund,
        PaymentStatus $newStatus,
        array $payload = [],
        ?PaymentCallbackData $callbackData = null
    ): void;
}

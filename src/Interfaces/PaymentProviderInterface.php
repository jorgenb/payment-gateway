<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
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
    public function initiate(Payment $payment, PaymentProviderConfig $config): PaymentResponse;

    /**
     * Charges a previously reserved payment.
     *
     * This method captures the specified amount from a reserved payment.
     * It should support idempotency to prevent duplicate charges.
     *
     * @return PaymentResponse The response containing the charge status and details.
     */
    public function charge(Payment $payment, PaymentProviderConfig $config): PaymentResponse;

    /**
     * Refunds a previously settled transaction (a charged payment).
     * The refunded amount will be transferred back to
     * the customer's account.
     *
     * This can be a full or partial refund depending on provider support.
     *
     * @return RefundResponse The response containing the refund status.
     */
    public function refund(PaymentRefund $refund, PaymentProviderConfig $config): RefundResponse;

    /**
     * Cancels a reserved payment before it is charged.
     *
     * Some providers only allow full cancellation.
     *
     * @return PaymentResponse The response containing the cancel status.
     */
    public function cancel(Payment $payment, PaymentProviderConfig $config): PaymentResponse;

    /**
     * Handles callbacks/webhooks from the payment provider.
     *
     * Payment providers send multiple callbacks during a payment lifecycle.
     * This method should handle and map different events appropriately.
     */
    public function handleCallback(PaymentCallbackData $data): void;

    /**
     * Records a payment event in the database.
     *
     * @param  Payment  $payment  The payment model instance
     * @param  array  $payload  Additional event data
     */
    public function recordPaymentEvent(
        Payment $payment,
        PaymentStatus $status,
        array $payload = [],
    ): void;

    /**
     * Records a refund event and updates the refund status.
     *
     * @param  PaymentRefund  $refund  The refund model instance
     * @param  array  $payload  Additional event data
     */
    public function recordRefundEvent(
        PaymentRefund $refund,
        PaymentStatus $status,
        array $payload = [],
    ): void;
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Events\ExternalPaymentEvent;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use InvalidArgumentException;

abstract class BasePaymentProvider implements PaymentProviderInterface
{
    /**
     * Handles callbacks/webhooks from the payment provider.
     *
     * Payment providers send multiple callbacks during a payment lifecycle.
     * This method should handle and map different events appropriately.
     */
    public function handleCallback(PaymentCallbackData $data): void
    {
        $this->recordExternalEvent($data);
    }

    /**
     * Dispatches an external payment event for a given provider.
     *
     * This method is typically triggered by webhooks/callbacks sent from the payment provider
     * and is used to map external status updates to internal events in the system.
     */
    public function recordExternalEvent(
        PaymentCallbackData $data
    ): void {
        ExternalPaymentEvent::dispatch($data);
    }

    /**
     * Records a payment event and updates the payment status.
     *
     * @param  Payment  $payment  The payment model instance
     * @param  PaymentStatus  $newStatus  New payment status
     * @param  array  $payload  Additional event data
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function recordPaymentEvent(
        Payment $payment,
        PaymentStatus $newStatus,
        array $payload = [],
        ?PaymentCallbackData $callbackData = null
    ): void {
        PaymentEvent::dispatch(
            $payment,
            $newStatus,
            $payload,
            $payment->getAmountAttribute(),
            $callbackData
        );
    }

    /**
     * Records a refund event and updates the refund status.
     *
     * @param  PaymentRefund  $refund  The refund model instance
     * @param  PaymentStatus  $newStatus  New refund status
     * @param  array  $payload  Additional event data
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function recordRefundEvent(
        PaymentRefund $refund,
        PaymentStatus $newStatus,
        array $payload = [],
        ?PaymentCallbackData $callbackData = null
    ): void {
        RefundEvent::dispatch(
            $refund,
            $newStatus,
            $payload,
            $refund->getAmountAttribute(),
            $callbackData
        );
    }

    /**
     * Ensures that the payment is in the expected status before proceeding.
     *
     * This method acts as a guard clause to prevent operations from being performed on payments
     * that are not in the required state (e.g., refunding a payment that has not been charged).
     *
     * @param  Payment  $payment  The payment instance to check
     * @param  PaymentStatus  $expected  The expected status required for the operation
     *
     * @throws InvalidArgumentException If the payment is not in the expected status
     */
    protected function ensureStatus(Payment $payment, PaymentStatus $expected): void
    {
        if ($payment->status !== $expected) {
            throw new InvalidArgumentException("Payment must be in {$expected->value} status.");
        }
    }
}

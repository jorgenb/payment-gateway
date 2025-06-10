<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Rules;

use Brick\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Bilberry\PaymentGateway\Models\Payment;

class RefundDoesNotExceedChargedAmount implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $paymentId = request('payment_id');
        $payment = Payment::with('refunds')->find($paymentId);

        if ( ! $payment) {
            $fail('The selected payment is invalid.');
            return;
        }

        /** @var Money $charged */
        $charged = $payment->totalChargedAmount;
        /** @var Money $refunded */
        $refunded = $payment->totalRefundedAmount;
        $pending = Money::ofMinor($value, $payment->currency);

        if ($refunded->plus($pending)->isGreaterThan($charged)) {
            $fail('The total of already refunded and pending refund amounts exceeds the charged amount for this payment.');
        }
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Events;

use Brick\Money\Money;
use Illuminate\Foundation\Events\Dispatchable;
use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;

readonly class PaymentEvent
{
    use Dispatchable;

    public function __construct(
        public Payment $payment,
        public PaymentStatus $newStatus,
        public array $payload = [],
        public ?Money $amount = null,
        public ?PaymentCallbackData $callbackData = null,
    ) {
    }
}

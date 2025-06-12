<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Events;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Brick\Money\Money;
use Illuminate\Foundation\Events\Dispatchable;

readonly class PaymentEvent
{
    use Dispatchable;

    public function __construct(
        public Payment $payment,
        public PaymentStatus $newStatus,
        public array $payload = [],
        public ?Money $amount = null,
        public ?PaymentCallbackData $callbackData = null,
    ) {}
}

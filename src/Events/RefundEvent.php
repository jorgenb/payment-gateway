<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Events;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Money\Money;
use Illuminate\Foundation\Events\Dispatchable;

readonly class RefundEvent
{
    use Dispatchable;

    public function __construct(
        public PaymentRefund $refund,
        public PaymentStatus $newStatus,
        public array $payload = [],
        public ?Money $amount = null,
        public ?PaymentCallbackData $callbackData = null,
    ) {}
}

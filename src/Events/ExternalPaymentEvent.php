<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Events;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Illuminate\Foundation\Events\Dispatchable;

class ExternalPaymentEvent
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentCallbackData $data,
    ) {}
}

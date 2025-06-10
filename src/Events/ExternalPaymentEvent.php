<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Bilberry\PaymentGateway\Data\PaymentCallbackData;

class ExternalPaymentEvent
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentCallbackData $data,
    ) {
    }
}

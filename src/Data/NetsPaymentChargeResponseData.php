<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Spatie\LaravelData\Data;

class NetsPaymentChargeResponseData extends Data
{
    public function __construct(
        public readonly string $chargeId,
        public readonly array $rawPayload = [],
    ) {
    }
}

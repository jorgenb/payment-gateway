<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Spatie\LaravelData\Data;

class NetsPaymentResponseData extends Data
{
    public function __construct(
        public readonly string $paymentId,
        public readonly array $rawPayload = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Spatie\LaravelData\Data;

class NetsPaymentRefundResponseData extends Data
{
    public function __construct(
        public readonly string $refundId,
        public readonly array $rawPayload = [],
    ) {
    }
}

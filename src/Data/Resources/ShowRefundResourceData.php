<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data\Resources;

use Spatie\LaravelData\Resource;

class ShowRefundResourceData extends Resource
{
    public string $id;

    public string $payment_id;

    public int $amount_minor;

    public string $currency;

    public string $status;

    public ?string $external_refund_id = null;

    public array $metadata;
}

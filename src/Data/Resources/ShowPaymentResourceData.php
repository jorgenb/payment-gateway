<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data\Resources;

use Bilberry\PaymentGateway\Enums\PayableType;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Resource;

class ShowPaymentResourceData extends Resource
{
    public string $id;

    public string $payable_id;

    public PayableType $payable_type;

    public PaymentProvider $provider;

    public string $type;

    public int $amount_minor;

    public string $currency;

    public PaymentStatus $status;

    public ?string $external_id;

    public ?string $external_charge_id;

    public ?string $reference;

    public ?Carbon $capture_at;

    public ?array $metadata;

    public Carbon $created_at;

    public Carbon $updated_at;

    public Money $total_charged_amount;

    public Money $total_refunded_amount;

    public Money $total_pending_refunded_amount;

    /** @var ShowRefundResourceData[] */
    public array $refunds;
}

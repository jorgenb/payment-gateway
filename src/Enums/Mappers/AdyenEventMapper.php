<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums\Mappers;

use Bilberry\PaymentGateway\Enums\PaymentStatus;

class AdyenEventMapper extends ProviderToStatusMapper
{
    protected static function map(): array
    {
        return [
            'AUTHORISATION'  => PaymentStatus::RESERVED,
            'CANCELLATION'   => PaymentStatus::CANCELLED,
            'REFUND'         => PaymentStatus::REFUNDED,
            'CAPTURE'        => PaymentStatus::CHARGED,
            'CAPTURE_FAILED' => PaymentStatus::FAILED,
            'REFUND_FAILED'  => PaymentStatus::REFUND_FAILED,
            'internal'       => PaymentStatus::PENDING,
            'processing'     => PaymentStatus::PROCESSING,
            'initiated'      => PaymentStatus::INITIATED,
        ];
    }

    protected static function defaultProviderEvent(): string
    {
        return 'AUTHORISATION';
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums\Mappers;

use Bilberry\PaymentGateway\Enums\PaymentStatus;

class StripeEventMapper extends ProviderToStatusMapper
{
    protected static function map(): array
    {
        return [
            'payment_intent.succeeded'                 => PaymentStatus::RESERVED,
            'payment_intent.canceled'                  => PaymentStatus::CANCELLED,
            'payment_intent.payment_failed'            => PaymentStatus::FAILED,
            'payment_intent.created'                   => PaymentStatus::INITIATED,
            'refund.created'                           => PaymentStatus::REFUNDED,
            'refund.failed'                            => PaymentStatus::REFUND_FAILED,
            'payment_intent.amount_capturable_updated' => PaymentStatus::RESERVED,
            'payment_intent.processing'                => PaymentStatus::PENDING,
            'payment_intent.requires_action'           => PaymentStatus::REQUIRES_ACTION,
            'internal'                                 => PaymentStatus::PENDING,
            'processing'                               => PaymentStatus::PROCESSING,
            'initiated'                                => PaymentStatus::INITIATED,
        ];
    }

    protected static function defaultProviderEvent(): string
    {
        return 'payment_intent.processing';
    }
}

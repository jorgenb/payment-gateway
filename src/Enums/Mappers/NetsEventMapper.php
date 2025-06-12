<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums\Mappers;

use Bilberry\PaymentGateway\Enums\PaymentStatus;

class NetsEventMapper extends ProviderToStatusMapper
{
    protected static function map(): array
    {
        return [
            'payment.cancel.created' => PaymentStatus::CANCELLED,
            'payment.cancel.failed' => PaymentStatus::FAILED,
            'payment.charge.created' => PaymentStatus::CHARGED,
            'payment.charge.failed' => PaymentStatus::FAILED,
            'payment.reservation.failed' => PaymentStatus::FAILED,
            'payment.created' => PaymentStatus::CREATED,
            'payment.refund.completed' => PaymentStatus::REFUNDED,
            'payment.refund.failed' => PaymentStatus::REFUND_FAILED,
            'payment.refund.initiated' => PaymentStatus::REFUND_INITIATED,
            'payment.reservation.created' => PaymentStatus::RESERVED,
            'internal' => PaymentStatus::PENDING,
            'processing' => PaymentStatus::PROCESSING,
            'initiated' => PaymentStatus::INITIATED,
        ];
    }
}

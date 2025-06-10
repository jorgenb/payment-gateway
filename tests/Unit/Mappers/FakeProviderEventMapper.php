<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Tests\Unit\Mappers;

use Bilberry\PaymentGateway\Enums\Mappers\ProviderToStatusMapper;
use Bilberry\PaymentGateway\Enums\PaymentStatus;

class FakeProviderEventMapper extends ProviderToStatusMapper
{
    protected static function map(): array
    {
        return [
            'event.known' => PaymentStatus::CHARGED,
        ];
    }

    protected static function defaultProviderEvent(): string
    {
        return 'default.event';
    }
}



<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Tests\Unit\Mappers\FakeProviderEventMapper;
use Illuminate\Support\Facades\Log;
use Bilberry\PaymentGateway\Enums\PaymentStatus;


beforeEach(function (): void {
    /** @phpstan-ignore-next-line */
    $this->logMock = Log::spy();
});

it('maps a known provider event to a PaymentStatus', function (): void {
    $status = FakeProviderEventMapper::fromProviderEvent('event.known');

    expect($status)->toBe(PaymentStatus::CHARGED);
});

it('returns UNHANDLED for an unknown provider event', function (): void {
    $status = FakeProviderEventMapper::fromProviderEvent('unknown.event');

    expect($status)->toBe(PaymentStatus::UNHANDLED);

    /** @phpstan-ignore-next-line */
    $this->logMock->shouldHaveReceived('warning')->once();
});

it('maps a known PaymentStatus to its provider event', function (): void {
    $event = FakeProviderEventMapper::toProviderEvent(PaymentStatus::CHARGED);

    expect($event)->toBe('event.known');
});

it('returns default event for an unmapped PaymentStatus', function (): void {
    $event = FakeProviderEventMapper::toProviderEvent(PaymentStatus::REQUIRES_ACTION);

    expect($event)->toBe('default.event');

    /** @phpstan-ignore-next-line */
    $this->logMock->shouldHaveReceived('warning')->once();
});

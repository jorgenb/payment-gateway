<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class, MocksNetsPayments::class);

beforeEach(function (): void {
    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $this->provider = new NetsPaymentProvider($resolver);
    $this->payment = Payment::factory()->nets()->pending()->create();
});

it('creates a payment and records events', function (): void {
    Event::fake([PaymentEvent::class]);
    $this->mockNetsSuccessfulPayment($paymentId = '1234567890');

    $this->provider->initiate($this->payment);

    Event::assertDispatched(PaymentEvent::class, fn ($event) => PaymentProvider::NETS->value === $event->payment->provider->value);
    Event::assertListening(
        PaymentEvent::class,
        Bilberry\PaymentGateway\Listeners\PaymentEventListener::class
    );
});

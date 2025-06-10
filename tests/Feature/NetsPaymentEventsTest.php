<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;

uses(RefreshDatabase::class, MocksNetsPayments::class);

beforeEach(function (): void {
    $this->provider = new NetsPaymentProvider();
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

<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->externalPaymentId = 'pi_test_123';
});

it('initiates a payment and records events', function (): void {
    $paymentIntent = PaymentIntent::constructFrom([
        'id'            => $this->externalPaymentId,
        'client_secret' => 'cs_test_abc',
        'status'        => 'requires_confirmation',
    ]);

    $paymentIntentsServiceMock = Mockery::mock();
    $paymentIntentsServiceMock->shouldReceive('create')->andReturn($paymentIntent);

    $stripeClientMock = Mockery::mock(StripeClient::class);
    $stripeClientMock->paymentIntents = $paymentIntentsServiceMock;

    $this->provider = new StripePaymentProvider($stripeClientMock);
    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor'       => 10000,
        'external_id'        => null,
        'external_charge_id' => null,
    ]);

    $response = $this->provider->initiate($payment);

    $payment->refresh();

    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->payment->provider)->toBe(PaymentProvider::STRIPE)
        ->and($response->metadata)->toHaveKey('clientSecret')
        ->and($payment)
        ->provider->toBe(PaymentProvider::STRIPE)
        ->amount_minor->toBe(10000)
        ->currency->toBe('NOK')
        ->external_id->toBe($this->externalPaymentId)
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value)
        );

});

it('handles failed payment creation', function (): void {
    $stripePaymentIntentMock = Mockery::mock();
    $stripePaymentIntentMock->shouldReceive('create')->andThrow(
        new InvalidRequestException('Invalid request', 400)
    );

    $stripeClientMock = Mockery::mock(StripeClient::class);
    $stripeClientMock->paymentIntents = $stripePaymentIntentMock;

    $this->provider = new StripePaymentProvider($stripeClientMock);

    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id'  => null,
    ]);

    expect(fn () => $this->provider->initiate($payment))
        ->toThrow(InvalidRequestException::class);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->external_id)->toBeNull();
});

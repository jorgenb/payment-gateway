<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Saloon\Exceptions\Request\ClientException;

uses(RefreshDatabase::class, MocksNetsPayments::class);

beforeEach(function (): void {
    $this->provider = new NetsPaymentProvider;
});

it('initiates a payment and records events', function ($paymentId): void {
    $this->mockNetsSuccessfulPayment($paymentId);

    $payment = Payment::factory()->nets()->pending()->create([
        'amount_minor' => 10000,
    ]);

    $config = new PaymentProviderConfig(
        contextId: 'test_context',
        apiKey: 'test_key_123',
        clientKey: 'test_key_123',
        merchantAccount: 'merchant_abc',
        environment: 'test',
        termsUrl: null,
        redirectUrl: null,
        webhookSigningSecret: null
    );

    $response = $this->provider->initiate($payment, $config);

    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->payment->provider)->toBe(PaymentProvider::NETS)
        ->and($response->responseData)->toHaveKey('paymentId');

    $payment = Payment::with('events')->first();
    expect($payment)
        ->provider->toBe(PaymentProvider::NETS)
        ->amount_minor->toBe(10000)
        ->currency->toBe('NOK')
        ->external_id->toBe($paymentId)
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED)
        );

})->with('nets initiate payment response');

it('handles failed payment creation', function (): void {
    $this->mockNetsFailedPayment400Status();

    $payment = Payment::factory()->nets()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
    ]);

    $config = new PaymentProviderConfig(
        contextId: 'test_context',
        apiKey: 'test_key_123',
        clientKey: 'test_key_123',
        merchantAccount: 'merchant_abc',
        environment: 'test',
        termsUrl: null,
        redirectUrl: null,
        webhookSigningSecret: null
    );

    expect(fn () => $this->provider->initiate($payment, $config))
        ->toThrow(ClientException::class);

    $payment->refresh();

    expect($payment)
        ->status->toBe(PaymentStatus::FAILED)
        ->external_id->toBeNull();
});

<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Set a custom mock HTTP client that implements ClientInterface
    ApiRequestor::setHttpClient(new class implements ClientInterface
    {
        public function request(
            $method,
            $absUrl,
            $headers,
            $params,
            $hasFile,
            $apiMode = 'v1',
            $maxNetworkRetries = null
        ) {
            $body = json_encode([
                'id' => 'pi_test_123',
                'client_secret' => 'cs_test_abc',
                'status' => 'requires_confirmation',
            ]);

            return [$body, 200, []];
        }
    });
});

afterEach(function (): void {
    // Reset the HTTP client to avoid side effects
    ApiRequestor::setHttpClient(null);
});

it('initiates a payment and records events', function (): void {
    $provider = new StripePaymentProvider;

    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
        'external_charge_id' => null,
    ]);

    $config = new \Bilberry\PaymentGateway\Data\PaymentProviderConfig(
        context_id: 'could_be_some_tenant_id',
        apiKey: 'test_key_123',
        merchantAccount: 'merchant_abc',
        environment: 'test',
        termsUrl: null,
        redirectUrl: null,
        webhookSigningSecret: null
    );

    $response = $provider->initiate($payment, $config);

    $payment->refresh();

    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->payment->provider)->toBe(PaymentProvider::STRIPE)
        ->and($response->metadata)->toHaveKey('clientSecret')
        ->and($payment)
        ->provider->toBe(PaymentProvider::STRIPE)
        ->amount_minor->toBe(10000)
        ->currency->toBe('NOK')
        ->external_id->toBe('pi_test_123')
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED)
        );
});

it('handles failed payment creation', function (): void {
    ApiRequestor::setHttpClient(new class implements ClientInterface
    {
        public function request(
            $method,
            $absUrl,
            $headers,
            $params,
            $hasFile,
            $apiMode = 'v1',
            $maxNetworkRetries = null
        ) {
            throw new \Stripe\Exception\InvalidRequestException('Invalid request', 400);
        }
    });

    $provider = new StripePaymentProvider;

    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
    ]);

    $config = new \Bilberry\PaymentGateway\Data\PaymentProviderConfig(
        context_id: 'could_be_some_tenant_id',
        apiKey: 'test_key_123',
        merchantAccount: 'merchant_abc',
        environment: 'test',
        termsUrl: null,
        redirectUrl: null,
        webhookSigningSecret: null
    );

    expect(fn () => $provider->initiate($payment, $config))
        ->toThrow(\Stripe\Exception\InvalidRequestException::class);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->external_id)->toBeNull();
});

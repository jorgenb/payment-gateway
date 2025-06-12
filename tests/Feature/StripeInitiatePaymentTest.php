<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
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
    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $provider = new StripePaymentProvider($resolver);

    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
        'external_charge_id' => null,
    ]);

    $response = $provider->initiate($payment);

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
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value)
        );
});

it('handles failed payment creation', function (): void {
    // Set a mock HTTP client that throws an exception on request
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

    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $provider = new StripePaymentProvider($resolver);

    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
    ]);

    expect(fn () => $provider->initiate($payment))
        ->toThrow(\Stripe\Exception\InvalidRequestException::class);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->external_id)->toBeNull();
});

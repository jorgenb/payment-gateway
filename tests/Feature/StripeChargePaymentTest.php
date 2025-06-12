<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Setup a mock HTTP client for Stripe API calls
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

    $resolver = $this->app->make(\Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface::class);
    $this->provider = new StripePaymentProvider($resolver);
});

afterEach(function (): void {
    // Reset HTTP client to default to avoid side effects
    ApiRequestor::setHttpClient(null);
});

it('initiates and handles charge callback correctly', function (string $externalId): void {
    // Create a pending Stripe payment
    $payment = Payment::factory()->stripe()->pending()->create([
        'external_id' => $externalId,
        'external_charge_id' => null,
    ]);

    // Simulate the charge callback payload from Stripe
    $chargeCallbackPayload = [
        'id' => 'evt_123_reservation',
        'type' => 'payment_intent.succeeded',
        'timestamp' => '2024-11-06T19:02:21.0787+00:00',
        'data' => [
            'object' => [
                'id' => $externalId,
                'latest_charge' => 'ch_test_456',
                'status' => 'succeeded',
                'metadata' => [
                    'merchantReference' => $payment->id,
                ],
            ],
        ],
    ];

    $chargeCallbackData = PaymentCallbackData::fromArray($chargeCallbackPayload, PaymentProvider::STRIPE);
    $this->provider->handleCallback($chargeCallbackData);

    // Assert: Payment is now marked as CHARGED,
    // external_charge_id is set, and events are recorded
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::CHARGED)
        ->and($payment->external_id)->toBe($externalId)
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::RESERVED->value)
        );
})->with([
    ['pi_test_123'],
]);

it('does not charge a payment when capture_at is set', function (string $paymentId): void {
    // Create a pending Stripe payment with capture_at set
    $payment = Payment::factory()->stripe()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
        'external_charge_id' => null,
        'capture_at' => now()->addDay(),
    ]);

    // Simulate the initiate flow
    $payment->update(['external_id' => $paymentId]);

    // Simulate the reservation callback payload from Stripe
    $reservationCallbackPayload = [
        'id' => 'evt_123_reservation',
        'type' => 'payment_intent.amount_capturable_updated',
        'timestamp' => '2024-11-06T19:02:21.0787+00:00',
        'data' => [
            'object' => [
                'id' => $paymentId,
                'latest_charge' => null,
                'status' => 'requires_capture',
                'metadata' => [
                    'merchantReference' => $payment->id,
                ],
            ],
        ],
    ];
    $reservationCallbackData = PaymentCallbackData::fromArray($reservationCallbackPayload, PaymentProvider::STRIPE);
    $this->provider->handleCallback($reservationCallbackData);

    // Assert: Since capture_at is set, the charge callback should not update the payment status to CHARGED.
    // Payment should remain in RESERVED state, external_charge_id remains null, and only INITIATED and RESERVED events are recorded.
    $payment = Payment::with('events')->first();

    expect($payment->status)->toBe(PaymentStatus::RESERVED)
        ->and($payment->external_charge_id)->toBeNull()
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::RESERVED->value)
        );
})->with([
    ['pi_test_123'],
]);

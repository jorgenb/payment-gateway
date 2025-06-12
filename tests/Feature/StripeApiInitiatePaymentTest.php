<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Set a global Stripe HTTP client that fakes PaymentIntent creation.
    \Stripe\ApiRequestor::setHttpClient(new class implements \Stripe\HttpClient\ClientInterface
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
            // Only intercept PaymentIntent create requests.
            // Return a fake PaymentIntent structure.
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
    // Reset the Stripe HTTP client to default (null) after each test.
    \Stripe\ApiRequestor::setHttpClient(null);
});

it('initiates a stripe payment via the api and records events', function ($externalPaymentId): void {

    $payload = [
        'provider' => 'stripe',
        'currency' => 'NOK',
        'amount_minor' => 10000,
        'payable_id' => FakePayable::factory()->create()->id,
        'payable_type' => 'fake_payable',
        'capture_at' => null,
    ];

    $response = $this
        ->withoutMiddleware()
        ->post(route('api.payments.store', PaymentProvider::STRIPE), $payload);

    $response->assertCreated();

    $payment = Payment::with('events')->first();

    expect($payment)->not()->toBeNull()
        ->and($payment->events)->toHaveCount(1)
        ->and($payment->status)->toBe(PaymentStatus::INITIATED)
        ->and($payment->external_id)->toBe($externalPaymentId);
})->with(['externalPaymentId' => 'pi_test_123']);

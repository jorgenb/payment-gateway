<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $paymentIntent = PaymentIntent::constructFrom([
        'id'            => 'pi_test_123',
        'client_secret' => 'cs_test_abc',
        'status'        => 'requires_confirmation',
    ]);

    $paymentIntentServiceMock = Mockery::mock(PaymentIntentService::class);
    $paymentIntentServiceMock->shouldReceive('create')->andReturn($paymentIntent);

    $mockedStripeClient = new class ($paymentIntentServiceMock) extends StripeClient {
        public function __construct(public $paymentIntents)
        {
        }
    };

    $this->app->singleton(StripeClient::class, fn () => $mockedStripeClient);
});

it('initiates a stripe payment via the api and records events', function ($externalPaymentId): void {

    $payload = [
        'provider'     => 'stripe',
        'currency'     => 'NOK',
        'amount_minor' => 10000,
        'payable_id'   => FakePayable::factory()->create()->id,
        'payable_type' => 'fake_payable',
        'capture_at'   => null,
    ];

    $response = $this
        ->withoutMiddleware()
        ->post(route('api.payments.store', PaymentProvider::STRIPE), $payload);

    $response->assertCreated();

    $payment = Payment::with('events')->first();

    expect($payment)->not()->toBeNull()
        ->and($payment->events)->toHaveCount(1)
        ->and($payment->status)->toBe(PaymentStatus::INITIATED);
})->with(['externalPaymentId' => 'pi_test_123']);

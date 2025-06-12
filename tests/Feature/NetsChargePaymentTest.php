<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Http\Requests\NetsChargePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsCreatePaymentRequest;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(MocksNetsPayments::class);

beforeEach(function (): void {
    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $this->provider = new NetsPaymentProvider($resolver);
});

it('initiates and then charges a payment via the Nets provider and records events', function (string $paymentId): void {
    // Arrange: Prepare the mocks.

    MockClient::global([
        NetsCreatePaymentRequest::class => MockResponse::make([
            'paymentId' => $paymentId,
            'hostedPaymentPageUrl' => "https://test.nets.eu/payments/{$paymentId}",
        ], 201),
        NetsChargePaymentRequest::class => MockResponse::make([
            'chargeId' => '55a8e4e3d0394353b7b51a9137c6e720',
        ], 200),
    ]);

    // Create a pending Nets payment with capture_at null to trigger immediate charging.
    $payment = Payment::factory()->nets()->pending()->create([
        'amount_minor' => 10000,
        'capture_at' => null,
        'status' => PaymentStatus::PENDING,
    ]);

    // Act: Initiate the payment.
    $initResponse = $this->provider->initiate($payment);

    // Assert initiate response and payment state.
    expect($initResponse->status)->toBe(PaymentStatus::INITIATED)
        ->and($initResponse->payment->provider)->toBe(PaymentProvider::NETS)
        ->and($initResponse->responseData)->toHaveKey('paymentId');

    $payment = Payment::with('events')->first();
    expect($payment)
        ->provider->toBe(PaymentProvider::NETS)
        ->amount_minor->toBe(10000)
        ->currency->toBe('NOK')
        // ->external_id->toBe($paymentId)
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value)
        );

    // Simulate the reservation callback payload from Nets.
    $reservationCallbackPayload = [
        'id' => '924bc362374949dba0dbc11131d88487',
        'event' => 'payment.reservation.created',
        'timestamp' => '2024-11-06T19:02:21.0787+00:00',
        'data' => [
            'paymentId' => $paymentId,
            'myReference' => $payment->id,
        ],
    ];
    $reservationCallbackData = PaymentCallbackData::fromArray($reservationCallbackPayload, PaymentProvider::NETS);
    // Act: Process the reservation callback to update the payment status to RESERVED.
    $this->provider->handleCallback($reservationCallbackData);

    // Assert: Payment is updated to RESERVED.
    $payment = Payment::find($payment->id);
    expect($payment->status)->toBe(PaymentStatus::RESERVED);

    // Simulate the charge callback payload from Nets.
    $chargeCallbackPayload = [
        'id' => 'fe51ffdf8732479fa4cd90cc8c13b86c',
        'event' => 'payment.charge.created',
        'timestamp' => '2024-11-06T19:02:21.0781+00:00',
        'data' => [
            'charge_id' => '55a8e4e3d0394353b7b51a9137c6e720',
            'paymentId' => $paymentId,
            'myReference' => $payment->id,
        ],
    ];
    $chargeCallbackData = PaymentCallbackData::fromArray($chargeCallbackPayload, PaymentProvider::NETS);
    // Act: Process the charge callback to update the payment status to CHARGED.
    $response = $this->provider->handleCallback($chargeCallbackData);

    // Assert: Payment is now marked as CHARGED and both events are recorded.
    $payment = Payment::with('events')->first();
    expect($payment->status)->toBe(PaymentStatus::CHARGED)
        ->and($payment->external_id)->toBe($paymentId)
        ->and($payment->events)->toHaveCount(4)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value),
            fn ($event) => $event->event->toBe(PaymentStatus::RESERVED->value),
            fn ($event) => $event->event->toBe(PaymentStatus::PROCESSING->value),
            fn ($event) => $event->event->toBe(PaymentStatus::CHARGED->value)
        );
})->with([
    [Str::uuid()->toString()],
]);

it('does not charge a payment when capture_at is set', function (string $paymentId): void {
    // Arrange: Prepare the mock for a successful payment initiation only.
    $this->mockNetsSuccessfulPayment($paymentId);

    // Do not mock a charge response since charge should not be triggered.

    // Create a pending Nets payment with capture_at set (i.e. scheduled for later capture).
    $payment = Payment::factory()->nets()->pending()->create([
        'amount_minor' => 10000,
        'capture_at' => now()->addDay(), // capture_at is set
    ]);

    // Act: Initiate the payment.
    $initResponse = $this->provider->initiate($payment);

    // Assert: Verify the initiation response.
    expect($initResponse->status)->toBe(PaymentStatus::INITIATED)
        ->and($initResponse->responseData)->toHaveKey('paymentId');

    // Reload payment and check that the INITIATED event is recorded.
    $payment = Payment::with('events')->first();
    expect($payment->events)->toHaveCount(1)
        ->sequence(fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value));

    // Simulate the reservation callback payload from Nets.
    $reservationCallbackPayload = [
        'id' => '924bc362374949dba0dbc11131d88487',
        'event' => 'payment.reservation.created',
        'timestamp' => '2024-11-06T19:02:21.0787+00:00',
        'data' => [
            'paymentId' => $paymentId,
            'myReference' => $payment->id,
        ],
    ];
    $reservationCallbackData = PaymentCallbackData::fromArray($reservationCallbackPayload, PaymentProvider::NETS);
    $this->provider->handleCallback($reservationCallbackData);

    // Assert: Payment remains in RESERVED state, external_id is null, and only INITIATED + RESERVED events are recorded.
    $payment = Payment::with('events')->first();
    expect($payment->status)->toBe(PaymentStatus::RESERVED)
        ->and($payment->external_id)->toBe($paymentId)
        ->and($payment->events)->toHaveCount(2)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value),
            fn ($event) => $event->event->toBe(PaymentStatus::RESERVED->value),
        );
})->with([
    [Str::uuid()->toString()],
]);

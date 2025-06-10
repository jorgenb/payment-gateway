<?php

declare(strict_types=1);

namespace Tests\Feature\Payments\Api;

use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;

uses(MocksNetsPayments::class);

it('initiates a payment via the API and records events', function ($paymentId): void {
    $this->mockNetsSuccessfulPayment($paymentId);

    $payload = [
        'provider'     => 'nets',
        'currency'     => 'NOK',
        'amount_minor' => 10000,
        'payable_id'   => FakePayable::factory()->create()->id,
        'payable_type' => 'fake_payable',
        'capture_at'   => null,
    ];

    $response = $this
        ->withoutMiddleware()
        ->post(route('api.payments.store', PaymentProvider::NETS), $payload);

    $response->assertCreated();

    $payment = Payment::with('events')->first();

    expect($payment)->not()->toBeNull()
        ->and($payment->events)->toHaveCount(1)
        ->and($payment->status)->toBe(PaymentStatus::INITIATED);
})->with('nets initiate payment response');

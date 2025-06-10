<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;

uses(RefreshDatabase::class, MocksNetsPayments::class);

beforeEach(function (): void {
    $this->provider = new NetsPaymentProvider();
});

it('handles refund failure gracefully', function (array $callbackData): void {

    $refundCallbackData = PaymentCallbackData::fromArray($callbackData, PaymentProvider::NETS);

    PaymentRefund::factory()->processing()->create([
        'external_refund_id' => $refundCallbackData->externalId,
    ]);

    $this->provider->handleCallback($refundCallbackData);

    $refund = PaymentRefund::first();

    expect($refund)
        ->not->toBeNull()
        ->and($refund->status)->toBe(PaymentStatus::REFUND_FAILED->value);

})->with('nets refund failed callback request');

it('returns error response when refund amount exceeds total charged amount', function (): void {
    $payment = Payment::factory()->nets()->charged()->create([
        'amount_minor' => 1000,
    ]);

    // Simulate existing refunded amount.
    PaymentRefund::factory()->create([
        'payment_id'          => $payment->id,
        'amount_minor' => 800,
        'status'              => PaymentStatus::REFUNDED->value,
    ]);

    // Simulate a pending refund attempt that exceeds the charged amount.
    $pendingRefund = PaymentRefund::factory()->make([
        'payment_id'          => $payment->id,
        'amount_minor' => 300,
        'status'              => PaymentStatus::PENDING,
    ]);

    $response = $this
        ->withoutMiddleware()
        ->postJson(route('api.refunds.store', $payment), [
            'provider'            => PaymentProvider::NETS->value,
            'payment_id'          => $payment->id,
            'amount_minor'          => $pendingRefund->amount_minor,
            'currency'            => $payment->currency,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount_minor']);
});

it('processes a completed refund callback', function (array $callbackData): void {
    $refundCallbackData = PaymentCallbackData::fromArray($callbackData, PaymentProvider::NETS);


    $refund = PaymentRefund::factory()->create([
        'external_refund_id' => $refundCallbackData->externalId,
        'status'             => PaymentStatus::PROCESSING,
    ]);

    $this->provider->handleCallback($refundCallbackData);

    $refund->refresh();

    expect($refund)
        ->not->toBeNull()
        ->and($refund->status)->toBe(PaymentStatus::REFUNDED->value);
})->with('nets refund completed callback request');

<?php

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;

uses(MocksNetsPayments::class);

it('handles a Nets reservation callback and sets payment status to RESERVED', function ($callbackPayload) {

    $this->mockNetsSuccessfulCharge($callbackPayload['data']['paymentId']);

    // Arrange: Seed the DB with an initiated payment
    $payment = Payment::factory()->withContext('tenant_b')->nets()->initiated()->create([
        'id' => $callbackPayload['data']['myReference'],
        'external_id' => $callbackPayload['data']['paymentId'],
        // 'capture_at' => now()->addDays(7),
    ]);

    // Act: POST the callback to the endpoint
    $response = $this
        ->withoutMiddleware([ProviderWebhookAuthorization::class])
        ->postJson(route('api.payments.callback', PaymentProvider::NETS->value), $callbackPayload);

    $response->assertSuccessful();
    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::RESERVED);
})->with('nets payment reservation created')->group('ci-flaky');

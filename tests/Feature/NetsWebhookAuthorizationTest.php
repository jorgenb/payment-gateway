<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Bilberry\PaymentGateway\Models\Payment;

beforeEach(function (): void {
    $this->externalId = '47c66622daf04b77b0f5a51bf8a670e7';
    $this->payment = Payment::factory()
        ->nets()
        ->pending()
        ->create([
            'external_id' => $this->externalId,
        ]);
    $this->webhookSecret = 'test-webhook-secret';
    config(['services.nets.webhook_secret' => $this->webhookSecret]);

    $this->validPayload = [
        'id'         => $this->externalId,
        'event'      => 'payment.checkout.completed',
        'timestamp'  => '2024-11-06T19:02:21.0750+00:00',
        'merchantId' => 100242833,
        'data'       => [
            'order' => [
                'amount' => ['amount' => '10000', 'currency' => 'NOK'],
            ],
        ],
    ];
});

test('callback succeeds with valid authorization header', function (): void {
    $response = $this->withHeaders([
        'Authorization' => $this->webhookSecret,
    ])->postJson(
        route('api.payments.callback', ['provider' => 'nets']),
        $this->validPayload
    );

    $response->assertSuccessful();
});

test('callback fails with invalid authorization header', function (): void {
    $response = $this->withHeaders([
        'Authorization' => 'invalid-secret',
    ])->postJson(
        route('api.payments.callback', ['provider' => 'nets']),
        $this->validPayload
    );

    $response->assertUnauthorized()
        ->assertJson(['error' => 'Unauthorized webhook request']);
});

test('callback fails without authorization header', function (): void {
    $response = $this->postJson(
        route('api.payments.callback', ['provider' => 'nets']),
        $this->validPayload
    );

    $response->assertUnauthorized()
        ->assertJson(['error' => 'Unauthorized webhook request']);
});

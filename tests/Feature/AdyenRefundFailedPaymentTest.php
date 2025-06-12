<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Adyen\Service\Checkout\ModificationsApi;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $modificationsApiMock = Mockery::mock(ModificationsApi::class);
    $this->app->instance(ModificationsApi::class, $modificationsApiMock);
});

it('handles refund failed callback', function ($refundFailedCallbackPayload): void {

    $notificationItem = data_get($refundFailedCallbackPayload, 'notificationItems.0.NotificationRequestItem');
    $merchantReference = data_get($notificationItem, 'merchantReference');
    $externalId = data_get($notificationItem, 'pspReference');

    // Seed a payment with id matching the merchantReference
    $payment = Payment::factory()->adyen()->charged()->create(['id' => $merchantReference]);
    $refund = PaymentRefund::factory()->create([
        'payment_id' => $payment->id,
        'status' => PaymentStatus::PROCESSING,
        'external_refund_id' => $externalId,
    ]);

    // Fire the callback
    $response = $this
        ->withoutMiddleware()
        ->postJson(route('api.payments.callback', PaymentProvider::ADYEN->value), $refundFailedCallbackPayload);

    // Assert response
    $response->assertSuccessful();

    // Refresh and assert refund is marked as failed
    $refund->refresh();
    expect($refund->status)->toBe(PaymentStatus::REFUND_FAILED->value);
    // ->and($refund->failure_reason)->toBe('Refund Failed');

})->with('adyen refund failed callback request');

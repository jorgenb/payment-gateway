<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ProviderWebhookAuthorization::class]);
    Event::fake();
    $this->adyenPaymentProvider = Mockery::spy(AdyenPaymentProvider::class)->makePartial();
    $this->app->instance(AdyenPaymentProvider::class, $this->adyenPaymentProvider);
});

it('handles adyen callback events', function (array $data): void {
    $this->payment = Payment::factory()
        ->adyen()
        ->pending()
        ->create([
            'external_id' => $data['notificationItems'][0]['NotificationRequestItem']['pspReference'],
        ]);

    $response = $this->postJson(
  'payments/callback/adyen',
        $data
    );

    $response->assertSuccessful();

    $this->adyenPaymentProvider->shouldHaveReceived('handleCallback')->once();
})->with('adyen callback requests');

<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ProviderWebhookAuthorization::class]);
    Event::fake();
    $this->stripePaymentProvider = Mockery::spy(StripePaymentProvider::class)->makePartial();
    $this->app->instance(StripePaymentProvider::class, $this->stripePaymentProvider);
});

it('handles stripe callback events', function (array $data): void {
    $this->payment = Payment::factory()
        ->nets()
        ->pending()
        ->create([
            'external_id' => $data['data']['object']['id'],
        ]);

    $response = $this->postJson(
        route('api.payments.callback', [
            'provider' => PaymentProvider::STRIPE->value,
        ]),
        $data
    );

    $response->assertSuccessful();

    $this->stripePaymentProvider->shouldHaveReceived('handleCallback')->once();
})->with('stripe callback requests')->group('ci-flaky');

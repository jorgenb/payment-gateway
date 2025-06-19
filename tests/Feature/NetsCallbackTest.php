<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake();
    $this->netsPaymentProvider = Mockery::spy(NetsPaymentProvider::class)->makePartial();
    $this->app->instance(NetsPaymentProvider::class, $this->netsPaymentProvider);
    $this->withoutExceptionHandling();
});

it('handles nets callback events', function (array $data): void {

    Payment::factory()
        ->nets()
        ->pending()
        ->create([
            'id' => $data['data']['myReference'],
            'external_id' => $data['data']['paymentId'],
        ]);

    $response = $this->withoutMiddleware([ProviderWebhookAuthorization::class])->postJson(
        route('api.payments.callback', PaymentProvider::NETS->value), $data
    );

    $response->assertSuccessful();

    if (PaymentStatus::fromNetsEvent($data['event']) !== PaymentStatus::UNHANDLED) {
        $this->netsPaymentProvider->shouldHaveReceived('handleCallback')->once();
    }

})->with('nets callback requests')->group('ci-flaky');

<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Adyen\Service\Checkout\PaymentsApi;
use Adyen\Service\Checkout\ModificationsApi;
use Adyen\Model\Checkout\CreateCheckoutSessionResponse;
use Exception;

uses(RefreshDatabase::class);

it('initiates a payment and records events', function ($initiatePaymentResponse): void {

    $this->externalSessionId = $initiatePaymentResponse['id'];

    $sessionResponse = new CreateCheckoutSessionResponse($initiatePaymentResponse);

    $paymentsApiMock = Mockery::mock(PaymentsApi::class);
    $paymentsApiMock->shouldReceive('sessions')->andReturn($sessionResponse);
    $modificationsApiMock = Mockery::mock(ModificationsApi::class);

    app()->bind(PaymentsApi::class, fn () => $paymentsApiMock);
    app()->bind(ModificationsApi::class, fn () => $modificationsApiMock);

    $this->provider = new AdyenPaymentProvider($paymentsApiMock, $modificationsApiMock);
    $payment = Payment::factory()->adyen()->pending()->create([
        'amount_minor'       => 10000,
        'external_id'        => null,
        'external_charge_id' => null,
    ]);

    $response = $this->provider->initiate($payment);

    $payment->refresh();

    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->payment->provider)->toBe(PaymentProvider::ADYEN)
        ->and($response->metadata)->toHaveKey('sessionId')
        ->and($response->metadata)->toHaveKey('sessionData')
        ->and($payment)
        ->provider->toBe(PaymentProvider::ADYEN)
        ->amount_minor->toBe(10000)
        ->currency->toBe('NOK')
        ->and($payment->events)->toHaveCount(1)
        ->sequence(
            fn ($event) => $event->event->toBe(PaymentStatus::INITIATED->value)
        );


})->with('adyen initiate payment response');

it('handles failed payment creation', function (): void {
    $paymentsApiMock = Mockery::mock(PaymentsApi::class);
    $paymentsApiMock->shouldReceive('sessions')->andThrow(
        new Exception('Adyen session error')
    );

    $modificationsApiMock = Mockery::mock(ModificationsApi::class);

    app()->bind(PaymentsApi::class, fn () => $paymentsApiMock);
    app()->bind(ModificationsApi::class, fn () => $modificationsApiMock);

    $this->provider = new AdyenPaymentProvider($paymentsApiMock, $modificationsApiMock);

    $payment = Payment::factory()->adyen()->pending()->create([
        'external_id' => null,
    ]);

    expect(fn () => $this->provider->initiate($payment))
        ->toThrow(Exception::class);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->external_id)->toBeNull();
});

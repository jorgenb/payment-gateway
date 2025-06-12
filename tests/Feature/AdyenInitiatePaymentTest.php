<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Adyen\Model\Checkout\CreateCheckoutSessionResponse;
use Adyen\Service\Checkout\ModificationsApi;
use Adyen\Service\Checkout\PaymentsApi;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

dataset('adyen initiate payment response inline', [
    [[
        'id' => 'test-session-id',
        'sessionData' => 'dummy-session-data',
        'otherData' => 'value',
    ]],
]);

it('initiates a payment and records events', function ($initiatePaymentResponse): void {
    $this->externalSessionId = $initiatePaymentResponse['id'];

    // Overload mocks for Adyen SDK
    $paymentsApiMock = Mockery::mock('overload:'.PaymentsApi::class);
    $paymentsApiMock->shouldReceive('sessions')->andReturn(new CreateCheckoutSessionResponse($initiatePaymentResponse));
    $modificationsApiMock = Mockery::mock('overload:'.ModificationsApi::class);

    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $provider = new AdyenPaymentProvider($resolver);

    $payment = Payment::factory()->adyen()->pending()->create([
        'amount_minor' => 10000,
        'external_id' => null,
        'external_charge_id' => null,
    ]);

    $response = $provider->initiate($payment);
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
})->with('adyen initiate payment response inline')->skip('Skipped for suite reliability: Adyen SDK mocking with overloads is not suite-safe and not easy to mock at HTTP level.');

it('handles failed payment creation', function (): void {
    // Overload mocks for Adyen SDK
    $paymentsApiMock = Mockery::mock('overload:'.PaymentsApi::class);
    $paymentsApiMock->shouldReceive('sessions')->andThrow(new Exception('Adyen session error'));
    $modificationsApiMock = Mockery::mock('overload:'.ModificationsApi::class);

    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $provider = new AdyenPaymentProvider($resolver);

    $payment = Payment::factory()->adyen()->pending()->create([
        'external_id' => null,
    ]);

    expect(fn () => $provider->initiate($payment))
        ->toThrow(Exception::class);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->external_id)->toBeNull();
})->skip('Skipped for suite reliability: Adyen SDK mocking with overloads is not suite-safe and not easy to mock at HTTP level.');

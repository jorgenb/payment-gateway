<?php

use Adyen\Model\Checkout\CreateCheckoutSessionResponse;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Illuminate\Support\Str;

it('initiates an Adyen payment using the PaymentProviderConfigResolverInterface', function () {
    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);

    // Mock Adyen SDK dependencies using Mockery overloads
    $fakeSessionId = Str::uuid()->toString();
    $fakeSessionData = 'FAKE_SESSION_DATA';

    $response = new CreateCheckoutSessionResponse([
        'id' => $fakeSessionId,
        'sessionData' => $fakeSessionData,
    ]);

    $mockClient = Mockery::mock('overload:Adyen\Client');
    $mockClient->shouldReceive('setXApiKey')->andReturnSelf();
    $mockClient->shouldReceive('setEnvironment')->andReturnSelf();

    $mockPaymentsApi = Mockery::mock('overload:Adyen\Service\Checkout\PaymentsApi');
    $mockPaymentsApi->shouldReceive('sessions')
        ->once()
        ->andReturn($response);

    // Create the AdyenPaymentProvider (no dependencies injected)
    $provider = new AdyenPaymentProvider($resolver);

    // Create a Payment model (use factory or manual instantiation)
    /** @var Payment $payment */
    $payment = Payment::factory()->create([
        'currency' => 'NOK',
        'amount_minor' => 1000,
        'status' => 'pending',
    ]);

    // Act: Initiate payment
    $response = $provider->initiate($payment);

    // Assert: Payment was initiated with data from fake config
    expect($response->status)->toBe(\Bilberry\PaymentGateway\Enums\PaymentStatus::INITIATED)
        ->and($response->responseData['id'])->toBe($fakeSessionId)
        ->and($payment->fresh()->metadata['sessionId'])->toBe($fakeSessionId);
})->skip('Skipped for suite reliability: Adyen SDK mocking with overloads is not suite-safe and not easy to mock at HTTP level.');

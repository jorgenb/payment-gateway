<?php

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;

class MockStripeHttpClient implements \Stripe\HttpClient\ClientInterface
{
    public function request(
        $method,
        $absUrl,
        $headers,
        $params,
        $hasFile,
        $apiMode = 'v1',
        $maxNetworkRetries = null
    ) {
        // Return a fake payment intent response
        $response = [
            'id' => 'pi_fake',
            'object' => 'payment_intent',
            'status' => 'requires_payment_method',
        ];

        return [json_encode($response), 200, []];
    }
}

beforeEach(function () {
    \Stripe\ApiRequestor::setHttpClient(new MockStripeHttpClient);
});

afterEach(function () {
    \Stripe\ApiRequestor::setHttpClient(null);
});

it('initiates a Stripe payment using the PaymentProviderConfigResolverInterface', function () {
    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);

    $payment = Payment::factory()->create([
        'currency' => 'NOK',
        'amount_minor' => 1000,
        'status' => PaymentStatus::PENDING,
    ]);

    $provider = new StripePaymentProvider($resolver);
    $response = $provider->initiate($payment);

    expect($response->status)->toBe(PaymentStatus::INITIATED);
    expect($response->responseData['id'])->toBe('pi_fake');
    // expect($payment->fresh()->metadata['intentId'])->toBe('pi_fake');
});

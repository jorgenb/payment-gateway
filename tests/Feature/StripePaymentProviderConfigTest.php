<?php

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\PaymentGateway;

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

it('initiates a Stripe payment using the PaymentGateway service', function () {
    // Arrange: build payload as a plain array with required fields
    $payload = [
        'provider' => 'stripe',
        'currency' => 'NOK',
        'amount_minor' => 10000,
        'payable_id' => FakePayable::factory()->create()->id,
        'payable_type' => 'fake_payable',
        'capture_at' => null,
    ];

    $requestData = PaymentRequestData::from($payload);

    // Provide a PaymentProviderConfig for the test
    $config = new PaymentProviderConfig(
        context_id: 'test_tenant',
        apiKey: 'test_key_123',
        environment: 'test',
        merchantAccount: 'merchant_abc',
        termsUrl: null,
        redirectUrl: null,
        webhookSigningSecret: null
    );

    // Act
    $gateway = app(PaymentGateway::class);
    $response = $gateway->create($requestData, $config);

    // Assert
    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->responseData['id'])->toBe('pi_fake');
});

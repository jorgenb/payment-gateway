<?php

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\PaymentGateway;

beforeEach(function () {
    \Stripe\ApiRequestor::setHttpClient(new class implements \Stripe\HttpClient\ClientInterface
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
            $body = [
                'id' => 're_test_123',
                'object' => 'refund',
                'amount' => 10000,
                'status' => 'succeeded',
            ];

            return [
                json_encode($body), // raw body string
                200,                // HTTP code
                $body,              // decoded array
                [],                 // headers
            ];
        }
    });
});

it('initiates a refund via PaymentGateway and creates the refund in the database', function () {
    // Arrange: Create a charged payment and an associated refund in one go
    $payment = Payment::factory()
        ->stripe()
        ->charged()
        ->has(
            PaymentRefund::factory()->state([
                'amount_minor' => 10000,
                'currency' => 'NOK',
            ]),
            'refunds'
        )
        ->create([
            'amount_minor' => 10000,
            'currency' => 'NOK',
            'external_id' => 'pi_test_123',
        ]);

    $refund = $payment->refunds()->first();

    // Arrange: Resolve config using the resolver in the container
    $resolver = app(PaymentProviderConfigResolverInterface::class);
    $config = $resolver->resolve(PaymentProvider::STRIPE);

    // Act: Call the refund method
    $gateway = app(PaymentGateway::class);
    $response = $gateway->refund($refund, $config);

    // Assert
    expect($response->status)->toBe(PaymentStatus::REFUND_INITIATED);
    $refund->refresh();
    expect($refund->payment_id)->toBe($payment->id);
    expect($refund->amount_minor)->toBe(10000);
});

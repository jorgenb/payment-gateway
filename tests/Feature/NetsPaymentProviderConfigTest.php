<?php

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Tests\Support\MocksNetsPayments;

uses(MocksNetsPayments::class);

it('initiates a Nets payment using the PaymentProviderConfigResolverInterface', function () {
    $paymentId = 'fake_nets_payment_id';

    $this->mockNetsSuccessfulPayment($paymentId);

    $resolver = $this->app->make(PaymentProviderConfigResolverInterface::class);
    $provider = new NetsPaymentProvider($resolver);

    $payment = Payment::factory()->nets()->pending()->create([
        'currency' => 'NOK',
        'amount_minor' => 1000,
        'status' => PaymentStatus::PENDING,
    ]);

    $response = $provider->initiate($payment);

    expect($response->status)->toBe(PaymentStatus::INITIATED)
        ->and($response->responseData['paymentId'])->toBe($paymentId);
});

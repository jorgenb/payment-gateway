<?php

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Models\Payment;

it('resolves the correct config for context_id tenant_a', function () {
    $payment = Payment::factory()->create([
        'provider' => PaymentProvider::NETS,
        'context_id' => 'tenant_a',
    ]);

    $resolver = app(\Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface::class);
    $config = $resolver->resolve($payment->provider, $payment->context_id);

    expect($config->apiKey)->toBe('test_api_key')
        ->and($config->merchantAccount)->toBe('TestMerchant')
        ->and($config->redirectUrl)->toBe('https://example.com/return');
});

it('resolves the correct config for context_id tenant_b', function () {
    $payment = Payment::factory()->create([
        'provider' => PaymentProvider::NETS,
        'context_id' => 'tenant_b',
    ]);

    $resolver = app(\Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface::class);
    $config = $resolver->resolve($payment->provider, $payment->context_id);

    expect($config->apiKey)->toBe('api_key_for_tenant_b')
        ->and($config->merchantAccount)->toBe('MerchantB')
        ->and($config->redirectUrl)->toBe('https://example.com/b-return');
});

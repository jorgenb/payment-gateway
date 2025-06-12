<?php

namespace Bilberry\PaymentGateway\Factories;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Stripe\StripeClient;

class StripeClientFactory
{
    public function __construct(
        protected PaymentProviderConfigResolverInterface $configResolver
    ) {}

    public function make(?PaymentProviderConfig $config = null): StripeClient
    {
        $config ??= $this->configResolver->resolve('stripe');

        return new StripeClient($config->apiKey);
    }
}

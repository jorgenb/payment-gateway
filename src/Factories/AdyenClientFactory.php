<?php

namespace Bilberry\PaymentGateway\Factories;

use Adyen\Client;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;

class AdyenClientFactory
{
    public function __construct(
        protected PaymentProviderConfigResolverInterface $configResolver
    ) {}

    public function make(?PaymentProviderConfig $config = null): Client
    {
        $config ??= $this->configResolver->resolve('adyen');

        $client = new Client;
        $client->setXApiKey($config->apiKey);

        return $client;
    }
}

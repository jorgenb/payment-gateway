<?php

namespace Bilberry\PaymentGateway\Factories;

use Bilberry\PaymentGateway\Connectors\NetsConnector;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;

class NetsConnectorFactory
{
    public function __construct(
        protected PaymentProviderConfigResolverInterface $configResolver
    ) {}

    public function make(?PaymentProviderConfig $config = null): NetsConnector
    {
        $config ??= $this->configResolver->resolve('nets');

        return new NetsConnector($config);
    }
}

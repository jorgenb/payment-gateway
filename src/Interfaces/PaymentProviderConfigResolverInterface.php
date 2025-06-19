<?php

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Enums\PaymentProvider;

interface PaymentProviderConfigResolverInterface
{
    /**
     * Resolve and return the configuration for the given payment provider.
     *
     * @param  PaymentProvider  $provider  The payment provider to resolve config for.
     * @param  mixed|null  $context  Optional. Pass tenant model, ID, or any other context for multi-tenant systems.
     *                               If omitted (null), will resolve using default/global configuration—suitable for single-tenant setups.
     * @return PaymentProviderConfig Configuration for the payment provider.
     */
    public function resolve(PaymentProvider $provider, mixed $context = null): PaymentProviderConfig;
}

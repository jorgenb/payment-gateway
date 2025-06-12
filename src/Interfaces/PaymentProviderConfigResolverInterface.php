<?php

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Enums\PaymentProvider;

interface PaymentProviderConfigResolverInterface
{
    /**
     * Resolve and return the configuration for the given payment provider.
     *
     * @param  mixed  $context  Optional. Tenant model, id, or any context needed. Null for current/default.
     * @return PaymentProviderConfig Configuration for the payment provider.
     */
    public function resolve(PaymentProvider $provider, mixed $context = null): PaymentProviderConfig;
}

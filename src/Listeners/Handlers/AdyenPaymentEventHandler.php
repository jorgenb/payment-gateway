<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners\Handlers;

use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;

readonly class AdyenPaymentEventHandler extends AbstractPaymentEventHandler
{
    public function __construct(private AdyenPaymentProvider $adyenPaymentProvider)
    {
    }

    protected function resolvePaymentProvider(): PaymentProviderInterface
    {
        return $this->adyenPaymentProvider;
    }
}

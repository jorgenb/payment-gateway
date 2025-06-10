<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners\Handlers;

use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;

readonly class StripePaymentEventHandler extends AbstractPaymentEventHandler
{
    public function __construct(private StripePaymentProvider $stripePaymentProvider)
    {
    }

    protected function resolvePaymentProvider(): PaymentProviderInterface
    {
        return $this->stripePaymentProvider;
    }
}

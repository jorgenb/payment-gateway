<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Services;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;

class PaymentGateway
{
    /**
     * Returns an instance of a payment provider based on the provided provider enum.
     *
     * @param  PaymentProvider  $provider  The payment provider enum instance.
     */
    public static function getProvider(PaymentProvider $provider): NetsPaymentProvider|StripePaymentProvider|AdyenPaymentProvider
    {
        return match ($provider) {
            PaymentProvider::NETS => app(NetsPaymentProvider::class),
            PaymentProvider::STRIPE => app(StripePaymentProvider::class),
            PaymentProvider::ADYEN => app(AdyenPaymentProvider::class),
        };
    }
}

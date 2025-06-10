<?php

namespace Bilberry\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bilberry\PaymentGateway\PaymentGateway
 */
class PaymentGateway extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bilberry\PaymentGateway\PaymentGateway::class;
    }
}

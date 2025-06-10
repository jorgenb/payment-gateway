<?php

namespace Bilberry\PaymentGateway;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bilberry\PaymentGateway\Commands\PaymentGatewayCommand;

class PaymentGatewayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('payment-gateway')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_payment_gateway_table')
            ->hasCommand(PaymentGatewayCommand::class);
    }
}

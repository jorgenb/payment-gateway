<?php

namespace Bilberry\PaymentGateway;

use Bilberry\PaymentGateway\Commands\ConfigureAdyenWebhooksCommand;
use Bilberry\PaymentGateway\Commands\ConfigureStripeWebhooksCommand;
use Bilberry\PaymentGateway\Commands\PaymentGatewayCommand;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Bilberry\PaymentGateway\Providers\EventServiceProvider;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PaymentGatewayServiceProvider extends PackageServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function bootingPackage(): void
    {
        // Skip binding check when running under static analysis, tests, or CLI tools like PHPStan/Pest/PhpUnit/Composer
        //        $isCliTool = app()->runningInConsole();
        //
        //        if (! $isCliTool && ! $this->app->bound(PaymentProviderConfigResolverInterface::class)) {
        //            throw new \RuntimeException('You must bind PaymentProviderConfigResolverInterface in your application.');
        //        }

        $this->app->make(Router::class)
            ->aliasMiddleware('webhooks', ProviderWebhookAuthorization::class);

        // Allow publishing of the package database seeder to the consuming application's seeder directory
        $this->publishes([
            __DIR__ . '/../database/seeders/FakePayablesDatabaseSeeder.php' =>
                database_path('seeders/FakePayablesDatabaseSeeder.php'),
        ], 'payment-gateway-seeders');

        $this->publishes([
            __DIR__.'/../resources/assets/sounds' => public_path('vendor/payment-gateway/sounds'),
        ], 'public');
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('payment-gateway')
            ->hasRoutes(['api', 'web'])
            ->hasConfigFile()
            // TODO: Make commands tenant aware
            ->hasCommands([
                ConfigureStripeWebhooksCommand::class,
                ConfigureAdyenWebhooksCommand::class,
            ])
            ->hasViews('payment-gateway')
            ->hasMigrations([
                'create_fake_payables_table',
                'create_payments_table',
                'create_payment_events_table',
                'create_payment_refunds_table',
            ])
            ->hasCommand(PaymentGatewayCommand::class);
    }

    /**
     * This method is called after the package is registered.
     * Here we can bind services, register commands, and set up the application.
     */
    public function packageRegistered(): void
    {
        $this->app->register(EventServiceProvider::class);

        $bindProvider = function ($app) {
            $provider = request()->get('provider') ?? request()->route('provider');

            if (! $provider) {
                return null;
            }

            return match (PaymentProvider::tryFrom($provider)) {
                PaymentProvider::NETS => $app->make(NetsPaymentProvider::class),
                PaymentProvider::STRIPE => $app->make(StripePaymentProvider::class),
                PaymentProvider::ADYEN => $app->make(AdyenPaymentProvider::class),
                default => throw new InvalidArgumentException("Unsupported payment provider: {$provider}")
            };
        };

        $this->app->when(PaymentCallbackController::class)
            ->needs(PaymentProviderInterface::class)
            ->give($bindProvider);
    }
}

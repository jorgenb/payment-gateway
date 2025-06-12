<?php

namespace Bilberry\PaymentGateway;

use Bilberry\PaymentGateway\Commands\ConfigureAdyenWebhooksCommand;
use Bilberry\PaymentGateway\Commands\ConfigureStripeWebhooksCommand;
use Bilberry\PaymentGateway\Commands\PaymentGatewayCommand;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Controllers\PaymentsController;
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
        if (! $this->app->bound(PaymentProviderConfigResolverInterface::class)) {
            throw new \RuntimeException('You must bind PaymentProviderConfigResolverInterface in your application.');
        }

        $this->app->make(Router::class)
            ->aliasMiddleware('webhooks', ProviderWebhookAuthorization::class);
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
            ->hasRoute('api')
            ->hasConfigFile()
            ->hasViews()
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
        $this->commands([
            ConfigureStripeWebhooksCommand::class,
            ConfigureAdyenWebhooksCommand::class,
        ]);

        $this->app->register(EventServiceProvider::class);

        // Register payment providers with injected config resolver
        $this->app->singleton(AdyenPaymentProvider::class, function ($app) {
            return new AdyenPaymentProvider(
                $app->make(PaymentProviderConfigResolverInterface::class)
            );
        });
        //        $this->app->singleton(StripePaymentProvider::class, function ($app) {
        //            return new StripePaymentProvider(
        //                $app->make(PaymentProviderConfigResolverInterface::class)
        //            );
        //        });
        //        $this->app->singleton(NetsPaymentProvider::class, function ($app) {
        //            return new NetsPaymentProvider(
        //                $app->make(PaymentProviderConfigResolverInterface::class)
        //            );
        //        });

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

        $this->app->when(PaymentsController::class)
            ->needs(PaymentProviderInterface::class)
            ->give($bindProvider);
    }
}

<?php

namespace Bilberry\PaymentGateway;

use Adyen\Client as AdyenClient;
use Adyen\Environment;
use Adyen\Service\Checkout\ModificationsApi;
use Adyen\Service\Checkout\PaymentsApi;
use Adyen\Service\Management\WebhooksMerchantLevelApi;
use Bilberry\PaymentGateway\Commands\ConfigureAdyenWebhooksCommand;
use Bilberry\PaymentGateway\Commands\ConfigureStripeWebhooksCommand;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Controllers\PaymentsApiController;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
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
use Bilberry\PaymentGateway\Commands\PaymentGatewayCommand;
use Stripe\StripeClient;

class PaymentGatewayServiceProvider extends PackageServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function bootingPackage(): void
    {
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

        $this->app->singleton(NetsPaymentProvider::class);
        $this->app->singleton(StripePaymentProvider::class);
        $this->app->singleton(AdyenPaymentProvider::class);

        $this->app->singleton(StripeClient::class, function () {
            $secret = config('services.stripe.secret');

            if ( ! is_string($secret) || empty($secret)) {
                throw new InvalidArgumentException('Stripe secret is not configured properly.');
            }

            return new StripeClient($secret);
        });
        $this->app->singleton(AdyenClient::class, function () {

            $secret = config('services.adyen.api_key');

            if ( ! is_string($secret) || empty($secret)) {
                throw new InvalidArgumentException('Adyen secret is not configured properly.');
            }

            $client = new AdyenClient();
            $client->setXApiKey($secret);
            $environment = app()->environment(['local', 'staging', 'testing']) ? Environment::TEST : Environment::LIVE;
            $client->setEnvironment($environment);
            return $client;
        });
        $this->app->singleton(WebhooksMerchantLevelApi::class, fn ($app) => new WebhooksMerchantLevelApi($app->make(AdyenClient::class)));

        $this->app->singleton(PaymentsApi::class, fn ($app) => new PaymentsApi($app->make(AdyenClient::class)));
        $this->app->singleton(ModificationsApi::class, fn ($app) => new ModificationsApi($app->make(AdyenClient::class)));


        $bindProvider = function ($app) {
            $provider = request()->get('provider') ?? request()->route('provider');

            if ( ! $provider) {
                return null;
            }

            return match (PaymentProvider::tryFrom($provider)) {
                PaymentProvider::NETS   => $app->make(NetsPaymentProvider::class),
                PaymentProvider::STRIPE => $app->make(StripePaymentProvider::class),
                PaymentProvider::ADYEN  => $app->make(AdyenPaymentProvider::class),
                default                 => throw new InvalidArgumentException("Unsupported payment provider: {$provider}")
            };
        };

        $this->app->when(PaymentCallbackController::class)
            ->needs(PaymentProviderInterface::class)
            ->give($bindProvider);

        $this->app->when(PaymentsApiController::class)
            ->needs(PaymentProviderInterface::class)
            ->give($bindProvider);
    }
}

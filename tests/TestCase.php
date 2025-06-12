<?php

namespace Bilberry\PaymentGateway\Tests;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\PaymentGatewayServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Bilberry\\PaymentGateway\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            PaymentGatewayServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('payment-gateway.nets.base_url', 'https://api.example.com');

        $app->singleton(PaymentProviderConfigResolverInterface::class, function () {
            return new class implements PaymentProviderConfigResolverInterface
            {
                public function resolve(PaymentProvider $provider, mixed $context = null): PaymentProviderConfig
                {
                    return new PaymentProviderConfig(
                        apiKey: 'test_api_key',
                        environment: 'test',
                        merchantAccount: 'TestMerchant',
                        termsUrl: null,
                        redirectUrl: 'https://example.com/return',
                        webhookSigningSecret: null,
                    );
                }
            };
        });

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}

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

        $app->singleton(PaymentProviderConfigResolverInterface::class, function () {
            return new class implements PaymentProviderConfigResolverInterface
            {
                public function resolve(PaymentProvider $provider, mixed $context = null): PaymentProviderConfig
                {
                    if ($context === 'tenant_b') {
                        return PaymentProviderConfig::from([
                            'contextId' => $context,
                            'apiKey' => 'api_key_for_tenant_b',
                            'clientKey' => 'client_key_for_tenant_b',
                            'environment' => 'test',
                            'merchantAccount' => 'MerchantB',
                            'termsUrl' => null,
                            'redirectUrl' => 'https://example.com/b-return',
                            'webhookSigningSecret' => 'test_webhook_secret'
                        ]);
                    }

                    // Default config for all other contexts
                    return PaymentProviderConfig::from([
                        'contextId' => null,
                        'apiKey' => 'test_api_key',
                        'clientKey' => 'test_client_key',
                        'environment' => 'test',
                        'merchantAccount' => 'TestMerchant',
                        'termsUrl' => null,
                        'redirectUrl' => 'https://example.com/return',
                        'webhookSigningSecret' => 'test_webhook_secret',
                    ]);
                }
            };
        });

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}

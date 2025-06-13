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
                    // Example: Use $context to change config if needed
                    if ($context === 'tenant_b') {
                        return PaymentProviderConfig::from([
                            'apiKey' => 'api_key_for_tenant_b',
                            'environment' => 'test',
                            'merchantAccount' => 'MerchantB',
                            'termsUrl' => null,
                            'redirectUrl' => 'https://example.com/b-return',
                            'webhookSigningSecret' => null,
                            'context_id' => 'tenant_b',
                        ]);
                    }

                    // Default config for all other contexts
                    return PaymentProviderConfig::from([
                        'apiKey' => 'test_api_key',
                        'environment' => 'test',
                        'merchantAccount' => 'TestMerchant',
                        'termsUrl' => null,
                        'redirectUrl' => 'https://example.com/return',
                        'webhookSigningSecret' => null,
                        'context_id' => $context ?? 'tenant_a',
                    ]);
                }
            };
        });

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}

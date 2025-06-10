<?php

namespace Bilberry\PaymentGateway\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Bilberry\PaymentGateway\PaymentGatewayServiceProvider;
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
            LaravelDataServiceProvider::class
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        config()->set('services.stripe.secret', 'sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        config()->set('services.adyen.api_key', 'test_AdyenApiKey');
        config()->set('services.nets.secret', 'test_NetsSecret');
        config()->set('services.nets.base_url', 'https://api.example.com');


        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}

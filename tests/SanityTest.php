<?php

it('can boot the application', function () {
    // Sanity: the app instance exists
    expect(app())->toBeInstanceOf(\Illuminate\Foundation\Application::class);

    // Sanity: the package service provider is loaded
    expect(app()->getLoadedProviders())->toHaveKey(\Bilberry\PaymentGateway\PaymentGatewayServiceProvider::class);

    // Example: the PaymentGateway facade resolves
    expect(\Bilberry\PaymentGateway\Facades\PaymentGateway::getFacadeRoot())->not()->toBeNull();

    // Example: config is published and default is an array
    expect(config('payment-gateway'))->toBeArray();

    // Example: package routes are loaded (optional, but nice)
    $routes = app('router')->getRoutes();
    expect($routes->getByName('api.payments.callback'))->not()->toBeNull();
});

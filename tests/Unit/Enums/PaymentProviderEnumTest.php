<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentProvider;

test('stripe payment provider enum extracts correct event, merchant reference and external id', function (array $payload): void {
    $provider = PaymentProvider::STRIPE;

    expect($provider->extractEventType($payload))->toBe(data_get($payload, 'type'))->not()->toBeNull()
        ->and($provider->extractMerchantReference($payload))->toBe(data_get($payload, 'data.object.metadata.merchantReference'))->not()->toBeNull()
        ->and($provider->resolveExternalId($payload))->toBe(data_get($payload, 'data.object.id'))->not()->toBeNull();
})->with('stripe callback requests');

test('nets payment provider enum extracts correct event, merchant reference and external id', function (array $payload): void {
    $provider = PaymentProvider::NETS;

    $event = $provider->extractEventType($payload);
    $merchantReference = $provider->extractMerchantReference($payload);
    $externalId = $provider->resolveExternalId($payload);

    expect($event)->toBe(data_get($payload, 'event'))->not()->toBeNull()
        ->and($merchantReference)->toBe(data_get($payload, 'data.myReference'))->not()->toBeNull()
        ->and($externalId)->toBe(data_get($payload, 'data.refundId') ?? data_get($payload, 'data.paymentId'))->not()->toBeNull();
})->with('nets callback requests');

test('adyen payment provider enum extracts correct event, merchant reference and external id', function (array $payload): void {
    $provider = PaymentProvider::ADYEN;

    $notificationItem = data_get($payload, 'notificationItems.0.NotificationRequestItem');

    $event = $provider->extractEventType($payload);
    $merchantReference = $provider->extractMerchantReference($payload);
    $externalId = $provider->resolveExternalId($payload);

    expect($event)->toBe(data_get($notificationItem, 'eventCode'))->not()->toBeNull()
        ->and($merchantReference)->toBe(data_get($notificationItem, 'merchantReference'))->not()->toBeNull()
        ->and($externalId)->toBe(data_get($notificationItem, 'pspReference'))->not()->toBeNull();
})->with('adyen callback requests');

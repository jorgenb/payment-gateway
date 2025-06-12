<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Data\PaymentRequestData;

it('passes validation with valid currency and amount_minor', function ($payload): void {
    $validator = Validator::make($payload, [
        'currency' => PaymentRequestData::rules()['currency'],
        'amount_minor' => PaymentRequestData::rules()['amount_minor'],
    ]);

    expect($validator->passes())->toBeTrue();
})->with([
    'valid NOK amount' => [['currency' => 'NOK', 'amount_minor' => 1000]],
    'valid large amount_minor' => [['currency' => 'USD', 'amount_minor' => 500000]],
]);

it('fails validation with invalid currency or amount_minor', function ($payload, $expectedErrors): void {
    $validator = Validator::make($payload, [
        'currency' => PaymentRequestData::rules()['currency'],
        'amount_minor' => PaymentRequestData::rules()['amount_minor'],
    ]);
    expect($validator->fails())->toBeTrue();
    foreach ($expectedErrors as $field) {
        expect($validator->errors()->has($field))->toBeTrue();
    }
})->with([
    'missing currency' => [['amount_minor' => 1000], ['currency']],
    'invalid currency format' => [['currency' => 'NOKK', 'amount_minor' => 1000], ['currency']],
    'zero amount_minor' => [['currency' => 'NOK', 'amount_minor' => 0], ['amount_minor']],
    'negative amount_minor' => [['currency' => 'USD', 'amount_minor' => -100], ['amount_minor']],
    'float amount_minor' => [['currency' => 'USD', 'amount_minor' => 10.5], ['amount_minor']],
]);

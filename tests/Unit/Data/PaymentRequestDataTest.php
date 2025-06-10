<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Brick\Money\Money;
use Bilberry\PaymentGateway\Enums\PayableType;

it('correctly converts amount_minor and currency into Money', function (): void {
    $paymentRequestData = new PaymentRequestData(
        provider: PaymentProvider::NETS->value,
        currency: 'NOK',
        amount_minor: 12345,
        payable_id: Illuminate\Support\Str::uuid()->toString(),
        payable_type: PayableType::FAKE_PAYABLE
    );

    $money = $paymentRequestData->getMoney();

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getMinorAmount()->toInt())->toBe(12345)
        ->and($money->getCurrency()->getCurrencyCode())->toBe('NOK');
});

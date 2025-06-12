<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('correctly calculates total charged and refunded amounts', function (): void {
    $payment = Payment::factory()->charged()->create([
        'amount_minor' => 5000,
        'currency' => 'NOK',
    ]);

    PaymentRefund::factory()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 1500,
        'status' => PaymentStatus::REFUNDED,
    ]);

    PaymentRefund::factory()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 1000,
        'status' => PaymentStatus::REFUNDED,
    ]);

    expect($payment->totalChargedAmount)
        ->toBeInstanceOf(Money::class)
        ->and($payment->totalChargedAmount->getMinorAmount()->toInt())->toBe(5000)
        ->and($payment->totalChargedAmount->getCurrency()->getCurrencyCode())->toBe('NOK')
        ->and($payment->totalRefundedAmount)
        ->toBeInstanceOf(Money::class)
        ->and($payment->totalRefundedAmount->getMinorAmount()->toInt())->toBe(2500)
        ->and($payment->totalRefundedAmount->getCurrency()->getCurrencyCode())->toBe('NOK');
});

it('correctly calculates total pending refunded amount', function (): void {
    $payment = Payment::factory()->charged()->create([
        'amount_minor' => 10000,
        'currency' => 'NOK',
    ]);

    PaymentRefund::factory()->refundInitiated()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 2000,
    ]);

    PaymentRefund::factory()->refundInitiated()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 1000,
    ]);

    PaymentRefund::factory()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 500,
        'status' => PaymentStatus::REFUNDED,
    ]);

    expect($payment->totalPendingRefundedAmount)
        ->toBeInstanceOf(Money::class)
        ->and($payment->totalPendingRefundedAmount->getMinorAmount()->toInt())->toBe(3000)
        ->and($payment->totalPendingRefundedAmount->getCurrency()->getCurrencyCode())->toBe('NOK');
});

it('returns zero if there are no pending refunds', function (): void {
    $payment = Payment::factory()->charged()->create([
        'amount_minor' => 8000,
        'currency' => 'NOK',
    ]);

    PaymentRefund::factory()->create([
        'payment_id' => $payment->id,
        'amount_minor' => 3000,
        'status' => PaymentStatus::REFUNDED,
    ]);

    expect($payment->totalPendingRefundedAmount)
        ->toBeInstanceOf(Money::class)
        ->and($payment->totalPendingRefundedAmount->getMinorAmount()->toInt())->toBe(0)
        ->and($payment->totalPendingRefundedAmount->getCurrency()->getCurrencyCode())->toBe('NOK');
});

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\Models\Payment;

class PaymentRefundFactory extends Factory
{
    protected $model = PaymentRefund::class;

    public function definition(): array
    {
        return [
            'payment_id'          => Payment::factory(),
            'amount_minor' => $this->faker->numberBetween(100, 10000),
            'currency'            => 'NOK',
            'status'              => PaymentStatus::REFUND_INITIATED,
            'external_refund_id'  => null,
            'metadata'            => [],
        ];
    }

    public function processing(): self
    {
        return $this->state([
            'status' => PaymentStatus::PROCESSING,
        ]);
    }

    public function refundInitiated(): self
    {
        return $this->state([
            'status' => PaymentStatus::REFUND_INITIATED,
            'external_refund_id' => $this->faker->uuid(),
        ]);
    }

    public function refunded(): self
    {
        return $this->state([
            'status' => PaymentStatus::REFUNDED,
            'external_refund_id' => $this->faker->uuid(),
        ]);
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status->value,
        ]);
    }

    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Database\Factories;

use Bilberry\PaymentGateway\Enums\PayableType;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    private const CURRENCIES = ['NOK'];

    public function definition(): array
    {
        return [
            'payable_id' => FakePayable::factory(),
            'payable_type' => PayableType::FAKE_PAYABLE,
            'provider' => PaymentProvider::ADYEN,
            'type' => 'one_time',
            'amount_minor' => fake()->numberBetween(1000, 1000000),
            'currency' => fake()->randomElement(self::CURRENCIES),
            'status' => PaymentStatus::PENDING,
            'external_id' => null,
            'external_charge_id' => null,
            'reference' => fake()->uuid(),
            'metadata' => [],
            'auto_capture' => true,
            'capture_at' => null,
        ];
    }

    public function nets(): self
    {
        return $this->state([
            'provider' => PaymentProvider::NETS->value,
        ]);
    }

    public function stripe(): self
    {
        return $this->state([
            'provider' => PaymentProvider::STRIPE->value,
        ]);
    }

    public function adyen(): self
    {
        return $this->state([
            'provider' => PaymentProvider::ADYEN->value,
        ]);
    }

    public function pending(): self
    {
        return $this->state([
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function charged(): self
    {
        return $this->state([
            'status' => PaymentStatus::CHARGED->value,
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => PaymentStatus::FAILED->value,
        ]);
    }

    public function reserved(): self
    {
        return $this->state([
            'status' => PaymentStatus::RESERVED->value,
        ]);
    }

    public function withAutoCapture(bool $value = true): self
    {
        return $this->state([
            'auto_capture' => $value,
        ]);
    }

    public function withCaptureAt(?CarbonImmutable $captureAt = null): self
    {
        return $this->state([
            'capture_at' => $captureAt ?? now()->addDay(),
        ]);
    }
}

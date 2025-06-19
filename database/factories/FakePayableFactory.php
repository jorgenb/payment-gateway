<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Database\Factories;

use Bilberry\PaymentGateway\Models\FakePayable;
use Illuminate\Database\Eloquent\Factories\Factory;

class FakePayableFactory extends Factory
{
    protected $model = FakePayable::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'data' => null,
        ];
    }
}

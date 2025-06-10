<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Bilberry\PaymentGateway\Models\FakePayable;

class FakePayableFactory extends Factory
{
    protected $model = FakePayable::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
        ];
    }

}

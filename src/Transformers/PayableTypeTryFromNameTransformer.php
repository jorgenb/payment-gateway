<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Transformers;

use Bilberry\PaymentGateway\Enums\PayableType;
use InvalidArgumentException;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

class PayableTypeTryFromNameTransformer implements Cast, Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): string
    {
        $normalized = mb_strtolower($value);

        foreach (PayableType::cases() as $case) {
            if (mb_strtolower($case->name) === $normalized) {
                return $case->value;
            }
        }

        throw new InvalidArgumentException("Invalid payable type: {$value}");
    }

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): PayableType
    {
        return PayableType::tryFromName($value);
    }
}

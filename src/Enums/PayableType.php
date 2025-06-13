<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums;

use Bilberry\PaymentGateway\Models\FakePayable;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Modules\Invoices\Models\Invoice;

enum PayableType: string
{
    /**
     * This is a stub payable model used in tests only.
     *
     * Its purpose is to make the payments module model-agnostic,
     * allowing it to function without a hard dependency on real domain models.
     *
     * This enum case is excluded in production environments to avoid accidental use.
     */
    case FAKE_PAYABLE = FakePayable::class;

    /**
     * Get the class name of the payable type.
     *
     * There probably is a more elegant way to do this,
     * but in this case we need to resolve the class name,
     * from the enum case based on the request and this works.
     *
     * For example, 'invoice' will return 'Modules\Invoices\Models\Invoice' as the enum value.
     *
     * There is no native way to get the class name from the string backed enum case.
     */
    public static function tryFromName(string $name): self
    {
        $normalized = mb_strtolower($name);

        foreach (self::cases() as $case) {
            // @phpstan-ignore-next-line
            if (App::environment('production') && $case === self::FAKE_PAYABLE) {
                continue;
            }

            if (mb_strtolower($case->name) === $normalized) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Invalid payable type name: {$name}");
    }
}

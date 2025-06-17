<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PayableType;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Transformers\PayableTypeTryFromNameTransformer;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\WithCastAndTransformer;
use Spatie\LaravelData\Data;

class PaymentRequestData extends Data
{
    public function __construct(
        #[Enum(PaymentProvider::class)]
        public string $provider,
        public string $currency,
        public int $amount_minor,
        public string $payable_id,
        #[WithCastAndTransformer(PayableTypeTryFromNameTransformer::class)]
        public readonly mixed $payable_type,
        public ?CarbonImmutable $capture_at = null,
        public ?bool $auto_capture = true,
        public ?string $context_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(PaymentProvider::class)],
            'currency' => 'required|size:3',
            'amount_minor' => 'required|integer|min:1',
            'payable_id' => 'required|string',
            'payable_type' => ['required', 'string', Rule::in(array_map(fn ($case) => mb_strtolower($case->name), PayableType::cases()))],
            'capture_at' => 'nullable|date|after_or_equal:now',
            'auto_capture' => 'nullable|boolean',
            'context_id' => 'nullable|string|max:255',
        ];
    }

    /**
     * Helper method to convert the integer amount to a Money instance.
     *
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function getMoney(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }
}

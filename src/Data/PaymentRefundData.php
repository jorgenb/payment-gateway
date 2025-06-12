<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Rules\RefundDoesNotExceedChargedAmount;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Data;

class PaymentRefundData extends Data
{
    public function __construct(
        #[Enum(PaymentProvider::class)]
        public readonly string $provider,
        public readonly string $payment_id,
        public readonly int $amount_minor,
        public readonly string $currency,
    ) {}

    public static function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(PaymentProvider::class)],
            'payment_id' => 'required|uuid|exists:payments,id',
            'amount_minor' => ['required', 'integer', 'min:1', new RefundDoesNotExceedChargedAmount],
            'currency' => 'size:3',
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            payment_id: $data['payment_id'],
            amount_minor: $data['amount_minor'],
            currency: $data['currency'],
        );
    }
}

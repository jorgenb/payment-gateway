<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Models\Payment;
use Spatie\LaravelData\Data;

class PaymentChargeData extends Data
{
    public function __construct(
        public readonly PaymentProvider $provider,
        public readonly string $paymentId,
        public readonly ?string $orderReference = null,
    ) {}

    public static function rules(): array
    {
        return [
            'provider' => 'required|in:'.implode(',', array_map(fn ($provider) => $provider->value, PaymentProvider::cases())),
            'paymentId' => 'required|string|exists:payments,id',
        ];
    }

    public static function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $paymentId = $validator->getData()['paymentId'] ?? null;

            if ($paymentId && Payment::where('id', $paymentId)->where('status', 'charged')->exists()) {
                $validator->errors()->add('paymentId', 'This payment has already been charged.');
            }
        });
    }
}

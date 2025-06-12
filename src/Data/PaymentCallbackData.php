<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

class PaymentCallbackData extends Data
{
    /**
     * DTO representing callback data from a payment provider.
     *
     * @param  PaymentProvider  $provider  The payment provider used for the transaction.
     * @param  array  $rawPayload  The raw JSON payload received from the payment provider.
     * @param  string|null  $merchantReference  Identifier for the payment in our system.
     * @param  string|null  $externalId  Identifier for the payment (or refund) in the provider's system.
     * @param  string|null  $eventType  Event type mapped to internal status.
     * @param  Money|null  $amount  The amount of money involved in the transaction.
     */
    public function __construct(
        #[FromRouteParameter('provider')]
        public readonly PaymentProvider $provider,
        public readonly array $rawPayload = [],
        public readonly ?string $merchantReference = null,
        public readonly ?string $externalId = null,
        public readonly ?string $eventType = null,
        public readonly ?Money $amount = null,
        public readonly ?PaymentStatus $newStatus = null,
        public readonly ?string $externalChargeId = null,
    ) {}

    public function hasMerchantReference(): bool
    {
        return ! empty($this->merchantReference);
    }

    public function hasExternalId(): bool
    {
        return ! empty($this->externalId);
    }

    public function isEventSupported(): bool
    {
        return match ($this->provider) {
            PaymentProvider::NETS => PaymentStatus::fromNetsEvent($this->eventType ?? '') !== PaymentStatus::UNHANDLED,
            PaymentProvider::STRIPE => PaymentStatus::fromStripeEvent($this->eventType ?? '') !== PaymentStatus::UNHANDLED,
            PaymentProvider::ADYEN => PaymentStatus::fromAdyenEvent($this->eventType ?? '') !== PaymentStatus::UNHANDLED,
        };
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public static function fromRequest(Request $request): self
    {
        $payload = $request->json()->all();
        $provider = PaymentProvider::tryFrom($request->route('provider'));

        return self::build($payload, $provider);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public static function fromArray(array $payload, PaymentProvider $provider): self
    {
        return self::build($payload, $provider);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    private static function build(array $payload, ?PaymentProvider $provider): self
    {
        return new self(
            provider: $provider,
            rawPayload: $payload,
            merchantReference: $provider?->extractMerchantReference($payload),
            externalId: $provider->resolveExternalId($payload),
            eventType: $provider->extractEventType($payload),
            amount: ($minor = $provider->extractAmountMinor($payload)) && ($currency = $provider->extractCurrency($payload))
                ? Money::ofMinor($minor, $currency)
                : null,
            newStatus: $provider->resolveStatus($provider->extractEventType($payload)),
            externalChargeId: $provider->extractExternalChargeId($payload),
        );
    }

    public function isRefundEvent(): bool
    {
        return str_contains(mb_strtolower(str_replace(['-', '_'], '', $this->eventType ?? '')), 'refund');
    }
}

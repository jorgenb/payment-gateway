<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums;

enum PaymentProvider: string
{
    /**
     * Nets payment provider.
     */
    case NETS = 'nets';

    /**
     * Stripe payment provider.
     */
    case STRIPE = 'stripe';

    /**
     * Adyen payment provider.
     */
    case ADYEN = 'adyen';

    public function extractMerchantReference(array $payload): ?string
    {
        // Loop through the list of potential payload paths.
        // Returns the FIRST non-null value encountered, allowing fallback support
        // for multiple known provider-specific structures.
        foreach ($this->merchantReferencePaths() as $path) {
            if ($value = data_get($payload, $path)) {
                return $value;
            }
        }

        return null;
    }

    public function extractExternalPaymentId(array $payload): ?string
    {
        foreach ($this->externalPaymentIdPaths() as $path) {
            if ($value = data_get($payload, $path)) {
                return $value;
            }
        }

        return null;
    }

    public function extractExternalRefundId(array $payload): ?string
    {
        foreach ($this->externalRefundIdPaths() as $path) {
            if ($value = data_get($payload, $path)) {
                return $value;
            }
        }

        return null;
    }

    public function extractEventType(array $payload): ?string
    {
        return data_get($payload, $this->eventTypePath());
    }

    public function extractExternalChargeId(array $payload): ?string
    {
        return match ($this) {
            self::NETS => data_get($payload, 'data.chargeId'),
            self::STRIPE => data_get($payload, 'data.object.latest_charge'),
            self::ADYEN => data_get($payload, 'notificationItems.0.NotificationRequestItem.pspReference'),
        };
    }

    public function extractAmountMinor(array $payload): ?int
    {
        return match ($this) {
            self::NETS => $this->intOrNull(data_get($payload, 'data.amount.amount')),
            self::STRIPE => $this->intOrNull(data_get($payload, 'data.object.amount')),
            self::ADYEN => $this->intOrNull(data_get($payload, 'notificationItems.0.NotificationRequestItem.amount.value')),
        };
    }

    public function extractCurrency(array $payload): string
    {
        return match ($this) {
            self::NETS => mb_strtoupper((string) data_get($payload, 'data.amount.currency')),
            self::STRIPE => mb_strtoupper((string) data_get($payload, 'data.object.currency')),
            self::ADYEN => mb_strtoupper((string) data_get($payload, 'notificationItems.0.NotificationRequestItem.amount.currency')),
        };
    }

    public function resolveExternalId(array $payload): ?string
    {
        return str_contains($this->extractEventType($payload) ?? '', 'refund')
            ? $this->extractExternalRefundId($payload)
            : $this->extractExternalPaymentId($payload);
    }

    public function resolveStatus(string $eventType): PaymentStatus
    {
        return match ($this) {
            self::NETS => PaymentStatus::fromNetsEvent($eventType),
            self::STRIPE => PaymentStatus::fromStripeEvent($eventType),
            self::ADYEN => PaymentStatus::fromAdyenEvent($eventType),
        };
    }

    private function merchantReferencePaths(): array
    {
        return match ($this) {
            self::NETS => [
                'data.myReference',
            ],
            self::STRIPE => [
                'data.object.metadata.merchantReference',
            ],
            self::ADYEN => [
                'notificationItems.0.NotificationRequestItem.merchantReference',
            ],
        };
    }

    private function externalPaymentIdPaths(): array
    {
        return match ($this) {
            self::NETS => [
                'data.paymentId',
            ],
            self::STRIPE => [
                'data.object.id',
            ],
            self::ADYEN => [
                'notificationItems.0.NotificationRequestItem.pspReference',
            ],
        };
    }

    private function externalRefundIdPaths(): array
    {
        return match ($this) {
            self::NETS => [
                'data.refundId',
            ],
            self::STRIPE => [
                'data.object.id',
            ],
            self::ADYEN => [
                'notificationItems.0.NotificationRequestItem.pspReference',
            ],
        };
    }

    private function eventTypePath(): string
    {
        return match ($this) {
            self::NETS => 'event',
            self::STRIPE => 'type',
            self::ADYEN => 'notificationItems.0.NotificationRequestItem.eventCode',
        };
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums\Mappers;

/**
 * This abstract base class defines a consistent interface for mapping
 * between external payment provider event names and the internal PaymentStatus enum.
 *
 * Concrete implementations override the static map() method to
 * provide a provider-specific mapping.
 *
 * This allows the application to standardize how provider events are converted
 * to internal status values, and vice versa, while centralizing fallback handling.
 */
use Illuminate\Support\Facades\Log;
use Bilberry\PaymentGateway\Enums\PaymentStatus;

abstract class ProviderToStatusMapper
{
    protected static function map(): array
    {
        return [];
    }

    /**
     * Maps an external provider event string to a corresponding internal PaymentStatus enum.
     *
     * This method uses the static map provided by the concrete mapper implementation.
     * If the event is not found, it falls back to PaymentStatus::PENDING.
     */
    public static function fromProviderEvent(string $event): PaymentStatus
    {
        $map = static::map();

        return $map[$event] ?? static::fallback($event);
    }

    /**
     * Maps an internal PaymentStatus enum to its corresponding external provider event string.
     *
     * Note: array_flip() cannot be used here because the map values are enums (objects),
     * which are not valid array keys in PHP. Instead, we loop through the map
     * and compare against the enum value to find the corresponding external event.
     */
    public static function toProviderEvent(PaymentStatus $status): string
    {
        foreach (static::map() as $externalEvent => $internalStatus) {
            if ($status === $internalStatus) {
                return $externalEvent;
            }
        }

        static::fallback($status->value);
        return static::defaultProviderEvent();
    }

    /**
     * Logs a warning and returns a default PaymentStatus::PENDING
     * when no matching status is found for a given event or status value.
     *
     * @param string $value The unmatched event or status string
     * @return PaymentStatus Fallback status (PENDING)
     *
     * This may occur if a payment provider (like Stripe) sends a callback
     * for an event type that is not yet mapped in the system. It's a safeguard
     * for forward compatibility and safe degradation.
     */
    protected static function fallback(string $value): PaymentStatus
    {
        Log::warning('Unhandled event or status: '.$value);
        return PaymentStatus::UNHANDLED;
    }

    /**
     * Returns a default provider event string when no matching event is found.
     *
     * Subclasses may override this to provide a provider-specific fallback event name.
     *
     * @return string
     */
    protected static function defaultProviderEvent(): string
    {
        return 'internal';
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums;

use Bilberry\PaymentGateway\Enums\Mappers\AdyenEventMapper;
use Bilberry\PaymentGateway\Enums\Mappers\NetsEventMapper;
use Bilberry\PaymentGateway\Enums\Mappers\StripeEventMapper;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Interfaces\PaymentEventHandlerInterface;

enum PaymentStatus: string
{
    /**
     * Payment has been created but not yet charged or reserved.
     */
    case CREATED = 'created';

    /**
     * Payment was canceled before completion.
     */
    case CANCELLED = 'cancelled';

    /**
     * Payment was successfully charged.
     */
    case CHARGED = 'charged';

    /**
     * Unable to charge payment.
     */
    case CHARGE_FAILED = 'charge_failed';

    /**
     * Payment operation failed to complete.
     *
     * This can be due to various reasons, such as insufficient funds,
     * network issues, or provider errors.
     */
    case FAILED = 'failed';

    /**
     * Payment was initiated with the provider.
     */
    case INITIATED = 'initiated';

    /**
     * Payment is pending further processing.
     */
    case PENDING = 'pending';

    /**
     * Payment was successfully refunded.
     */
    case REFUNDED = 'refunded';

    /**
     * Refund operation failed.
     *
     * This can be due to various reasons, such as client errors,
     * network issues, or provider errors.
     */
    case REFUND_FAILED = 'refund_failed';

    /**
     * The Refund process has been initiated.
     */
    case REFUND_INITIATED = 'refund_initiated';

    /**
     * Payment requires further action, e.g., 3D Secure authentication.
     */
    case REQUIRES_ACTION = 'requires_action';

    /**
     * Payment has been reserved but not yet captured.
     */
    case RESERVED = 'reserved';

    /**
     * Payment or refund is currently processing.
     */
    case PROCESSING = 'processing';

    /**
     * Status could not be mapped from the external provider.
     */
    case UNHANDLED = 'unhandled';

    /**
     * Dispatches the event to the appropriate payment event handler.
     *
     * @param  PaymentEventHandlerInterface  $handler
     * @param  PaymentEvent|RefundEvent  $event
     */
    public function handle(PaymentEventHandlerInterface $handler, PaymentEvent|RefundEvent $event): void
    {
        match ($this) {
            self::CANCELLED        => $handler->handleCancelCreated($event),
            self::CHARGED          => $handler->handleChargeCreated($event),
            self::CHARGE_FAILED    => $handler->handleChargeFailed($event),
            self::FAILED           => $handler->handleFailed($event),
            self::REFUNDED         => $handler->handleRefundCompleted($event),
            self::REFUND_INITIATED => $handler->handleRefundInitiated($event),
            self::REFUND_FAILED    => $handler->handleRefundFailed($event),
            self::RESERVED         => $handler->handleReservationCreated($event),
            self::CREATED          => $handler->handlePaymentCreated($event),
            self::PENDING          => $handler->handlePending($event),
            self::REQUIRES_ACTION  => $handler->handleRequiresAction($event),
            self::PROCESSING       => $handler->handleProcessing($event),
            self::INITIATED        => $handler->handleInitiated($event),
            self::UNHANDLED        => $handler->handleUnhandled($event),
        };
    }

    /**
     * Map a PaymentStatus to a Nets event string.
     *
     * @see NetsEventMapper
     */
    public function toNetsStatus(): string
    {
        return NetsEventMapper::toProviderEvent($this);
    }

    /**
     * Map a Nets event string to a PaymentStatus.
     *
     * @see NetsEventMapper
     */
    public static function fromNetsEvent(string $event): self
    {
        return NetsEventMapper::fromProviderEvent($event);
    }

    /**
     * Map a PaymentStatus to a Stripe event string.
     *
     * @see StripeEventMapper
     */
    public function toStripeStatus(): string
    {
        return StripeEventMapper::toProviderEvent($this);
    }

    /**
     * Map a Stripe event string to a PaymentStatus.
     *
     * @see StripeEventMapper
     */
    public static function fromStripeEvent(string $event): self
    {
        return StripeEventMapper::fromProviderEvent($event);
    }

    /**
     * Map a PaymentStatus to an Adyen event string.
     *
     * @see AdyenEventMapper
     */
    public function toAdyenStatus(): string
    {
        return AdyenEventMapper::toProviderEvent($this);
    }

    /**
     * Map an Adyen event string to a PaymentStatus.
     *
     * @see AdyenEventMapper
     */
    public static function fromAdyenEvent(string $event): self
    {
        return AdyenEventMapper::fromProviderEvent($event);
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Models\Payment;

interface PaymentEventHandlerInterface
{
    /**
     * Resolve the provider config for a given payment at runtime using the context_id.
     */
    public function resolveConfig(Payment $payment): PaymentProviderConfig;

    public function handle(PaymentEvent|RefundEvent $event): void;

    public function handleInitiated(PaymentEvent $event): void;

    public function handleUnhandled(PaymentEvent $event): void;

    public function handleCheckoutCompleted(PaymentEvent $event): void;

    public function handleChargeCreated(PaymentEvent $event): void;

    public function handleChargeFailed(PaymentEvent $event): void;

    public function handleFailed(PaymentEvent|RefundEvent $event): void;

    public function handleCancelCreated(PaymentEvent $event): void;

    public function handlePending(PaymentEvent|RefundEvent $event): void;

    public function handlePaymentCreated(PaymentEvent $event): void;

    public function handleReservationCreated(PaymentEvent $event): void;

    public function handleRefundCompleted(RefundEvent $event): void;

    public function handleRefundFailed(RefundEvent $event): void;

    public function handleRefundInitiated(RefundEvent $event): void;

    public function handleRequiresAction(PaymentEvent $event): void;

    public function handleProcessing(PaymentEvent|RefundEvent $event);
}

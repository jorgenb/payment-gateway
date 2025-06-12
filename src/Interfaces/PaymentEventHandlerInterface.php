<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Interfaces;

use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;

interface PaymentEventHandlerInterface
{
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

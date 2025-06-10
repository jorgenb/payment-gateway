<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Listeners\Handlers\AdyenPaymentEventHandler;
use Bilberry\PaymentGateway\Listeners\Handlers\NetsPaymentEventHandler;
use Bilberry\PaymentGateway\Listeners\Handlers\StripePaymentEventHandler;
use Bilberry\PaymentGateway\Models\PaymentEvent as PaymentEventLog;
use Throwable;

readonly class PaymentEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private NetsPaymentEventHandler $netsHandler,
        private StripePaymentEventHandler $stripeHandler,
        private AdyenPaymentEventHandler $adyenHandler,
    ) {
    }

    /**
     * Handle the event.
     * @throws Throwable
     */
    public function handle(PaymentEvent $event): void
    {
        // Log the payment event
        PaymentEventLog::create([
            'payment_id' => $event->payment->id,
            'event'      => $event->newStatus,
            'payload'    => $event->payload,
        ]);

        // Handle provider-specific logic
        match ($event->payment->provider) {
            PaymentProvider::NETS   => $this->netsHandler->handle($event),
            PaymentProvider::STRIPE => $this->stripeHandler->handle($event),
            PaymentProvider::ADYEN  => $this->adyenHandler->handle($event),
        };
    }
}

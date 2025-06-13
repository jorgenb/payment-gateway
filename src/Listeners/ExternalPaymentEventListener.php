<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Events\ExternalPaymentEvent;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Listeners\Handlers\AdyenPaymentEventHandler;
use Bilberry\PaymentGateway\Listeners\Handlers\NetsPaymentEventHandler;
use Bilberry\PaymentGateway\Listeners\Handlers\StripePaymentEventHandler;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentEvent as PaymentEventLog;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExternalPaymentEventListener implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly NetsPaymentEventHandler $netsHandler,
        private readonly StripePaymentEventHandler $stripeHandler,
        private readonly AdyenPaymentEventHandler $adyenHandler,
    ) {}

    /**
     * Handle the queued external payment event.
     *
     * This is where we perform the database query to resolve the Payment model
     * or the Refund model associated with the external callback and transform the external event into
     * a normalized internal PaymentEvent or RefundEvent. Once converted, the event is delegated
     * to the appropriate provider-specific handler for further processing.
     *
     * @throws Throwable
     */
    public function handle(ExternalPaymentEvent $event): void
    {
        $callbackData = $event->data;

        [$payment, $refund] = $this->resolvePaymentAndRefund($callbackData);

        PaymentEventLog::create([
            'payment_id' => $payment->id,
            'event' => $callbackData->newStatus,
            'payload' => (array) $callbackData,
        ]);

        $normalizedInternalEvent = $this->createInternalEvent($callbackData, $payment, $refund);

        match ($callbackData->provider) {
            PaymentProvider::NETS => $this->netsHandler->handle($normalizedInternalEvent),
            PaymentProvider::STRIPE => $this->stripeHandler->handle($normalizedInternalEvent),
            PaymentProvider::ADYEN => $this->adyenHandler->handle($normalizedInternalEvent),
        };
    }

    private function createInternalEvent($callbackData, Payment $payment, ?PaymentRefund $refund): PaymentEvent|RefundEvent
    {

        if ($callbackData->isRefundEvent()) {
            return new RefundEvent(
                refund: $refund,
                newStatus: $callbackData->newStatus,
                payload: $callbackData->rawPayload,
                amount: $callbackData->amount,
                callbackData: $callbackData,
            );
        }

        return new PaymentEvent(
            payment: $payment,
            newStatus: $callbackData->newStatus,
            payload: $callbackData->rawPayload,
            amount: $callbackData->amount,
            callbackData: $callbackData,
        );
    }

    /**
     * Resolves the Payment and PaymentRefund models from callback data.
     */
    private function resolvePaymentAndRefund($callbackData): array
    {
        $refund = null;

        if ($callbackData->isRefundEvent()) {
            $refund = PaymentRefund::where('external_refund_id', $callbackData->externalId)->firstOrFail();
            $payment = $refund->payment;
        } else {
            $payment = Payment::where('id', $callbackData->merchantReference)
                ->orWhere('external_id', $callbackData->externalId)
                ->firstOrFail();
        }

        return [$payment, $refund];
    }
}

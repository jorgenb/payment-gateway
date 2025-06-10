<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners\Handlers;

use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Interfaces\PaymentEventHandlerInterface;
use Illuminate\Support\Facades\Log;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Models\PaymentRefund;

abstract readonly class AbstractPaymentEventHandler implements PaymentEventHandlerInterface
{
    /**
     * Resolve the specific payment provider instance for the handler.
     */
    abstract protected function resolvePaymentProvider(): PaymentProviderInterface;

    /**
     * Dispatch the handling of the event to the appropriate status handler method.
     */
    final public function handle(PaymentEvent|RefundEvent $event): void
    {
        $event->newStatus->handle($this, $event);
    }

    /**
     * Handle a newly initiated payment.
     */
    public function handleInitiated(PaymentEvent $event): void
    {
        $event->payment->update(['status' => PaymentStatus::INITIATED]);
        Log::info(static::class.': Payment initiated', ['event' => $event]);
    }

    /**
     * Handle unknown or unmapped events.
     */
    public function handleUnhandled(PaymentEvent $event): void
    {
        Log::info(static::class.': Unhandled event', ['event' => $event]);
    }

    /**
     * Handle completion of the checkout process.
     */
    public function handleCheckoutCompleted(PaymentEvent $event): void
    {
        Log::info(static::class.': Checkout completed', ['event' => $event]);
    }

    /**
     * Handle a successful charge creation.
     */
    public function handleChargeCreated(PaymentEvent $event): void
    {
        $event->payment->update([
            'status'             => PaymentStatus::CHARGED,
            'external_charge_id' => $event->callbackData->externalChargeId
        ]);

        Log::info(static::class.': Charge created', ['event' => $event]);
    }

    public function handleChargeFailed(PaymentEvent $event): void
    {
        $event->payment->update([
            'status' => PaymentStatus::CHARGE_FAILED,
        ]);

        Log::info(static::class.': Charge failed', ['event' => $event]);
    }

    /**
     * Handle a failed charge attempt.
     */
    public function handleFailed(PaymentEvent|RefundEvent $event): void
    {
        ($event instanceof RefundEvent ? $event->refund : $event->payment)->update(['status' => PaymentStatus::FAILED]);

        Log::info(static::class.': Failed', ['event' => $event]);
    }

    /**
     * Handle cancellation of a payment.
     */
    public function handleCancelCreated(PaymentEvent $event): void
    {
        $event->payment->update(['status' => PaymentStatus::CANCELLED]);

        Log::info(static::class.': Payment cancelled', ['event' => $event]);
    }

    /**
     * Handle events where the payment is still pending.
     */
    public function handlePending(PaymentEvent|RefundEvent $event): void
    {
        Log::info(static::class.': Payment pending', ['event' => $event]);
    }

    /**
     * Handle the creation of a payment record.
     */
    public function handlePaymentCreated(PaymentEvent $event): void
    {
        Log::info(static::class.': Payment created', ['event' => $event]);
    }

    /**
     * Handle a payment reservation event and optionally auto-capture if allowed.
     */
    public function handleReservationCreated(PaymentEvent $event): void
    {
        $event->payment->update([
            'status'      => PaymentStatus::RESERVED,
            'external_id' => $event->callbackData->externalId
        ]);

        Log::info(static::class.': Payment reserved', ['event' => $event]);

        // Only auto-capture if capture_at is not set and auto_capture is explicitly enabled
        if (null === $event->payment->capture_at && true === $event->payment->auto_capture) {
            $event->payment->update(['status' => PaymentStatus::CHARGED]);
            Log::info(static::class.': Auto-capture enabled. Marking payment as charged.', ['event' => $event]);
        }
    }

    /**
     * Handle a refund completion, update or create the refund record.
     */
    public function handleRefundCompleted(RefundEvent $event): void
    {
        if (PaymentStatus::CANCELLED === $event->refund->payment->status) {
            Log::info(static::class.': Refund event received for already cancelled payment. Skipping further processing.', ['event' => $event]);
            return;
        }

        $externalRefundId = $event->callbackData->externalId;
        $amount = $event->amount;

        if ($externalRefundId) {
            $refund = $event->refund->where('external_refund_id', $externalRefundId)->first();

            if ($refund) {
                $refund->update(['status' => PaymentStatus::REFUNDED]);
            } else {
                PaymentRefund::create([
                    'external_refund_id'  => $externalRefundId,
                    'amount_minor' => $amount->getMinorAmount(),
                    'currency'            => $amount->getCurrency(),
                    'status'              => PaymentStatus::REFUNDED,
                    'metadata'            => [],
                ]);

                Log::info(static::class.': Refund completed for non-existing refund. Creating new refund record.', ['event' => $event]);

            }
        }

        Log::info(static::class.': Refund completed', ['event' => $event]);
    }

    /**
     * Handle a failed refund operation.
     */
    public function handleRefundFailed(RefundEvent $event): void
    {
        $event->refund->update(['status' => PaymentStatus::REFUND_FAILED]);
        Log::info(static::class.': Refund failed', ['event' => $event]);
    }

    /**
     * Handle the initiation of a refund process.
     */
    public function handleRefundInitiated(RefundEvent $event): void
    {
        $event->refund->update(['status' => PaymentStatus::REFUND_INITIATED]);
        Log::info(static::class.': Refund initiated', ['event' => $event]);
    }

    /**
     * Handle cases where the payment requires further user interaction.
     */
    public function handleRequiresAction(PaymentEvent $event): void
    {
        Log::info(static::class.': Requires action', ['event' => $event]);
    }

    /**
     * Handle a payment that is being processed.
     */
    public function handleProcessing(PaymentEvent|RefundEvent $event): void
    {
        Log::info(static::class.': Processing', ['event' => $event]);
    }


}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Listeners\Handlers;

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Listeners\PaymentEventListener;
use Bilberry\PaymentGateway\PaymentGateway;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Handles payment events from the Nets payment provider.
 *
 * @see PaymentEventListener
 */
readonly class NetsPaymentEventHandler extends AbstractPaymentEventHandler
{
    public function __construct(
        private NetsPaymentProvider $netsPaymentProvider,
        private PaymentGateway $paymentGateway
    ) {}

    protected function resolvePaymentProvider(): PaymentProviderInterface
    {
        return $this->netsPaymentProvider;
    }

    /**
     * Handle a payment reservation event and optionally auto-capture if allowed.
     */
    public function handleReservationCreated(PaymentEvent $event): void
    {
        $event->payment->update([
            'status' => PaymentStatus::RESERVED,
            'external_id' => $event->callbackData->externalId,
        ]);

        Log::info(static::class.': Payment reserved', ['event' => $event]);

        // Only capture if capture_at is not set.
        if ($event->payment->capture_at === null) {
            try {
                Log::info(static::class.': Capturing payment', [
                    'event' => $event,
                ]);

                $config = $this->resolveConfig($event->payment);

                $this->paymentGateway->charge($event->payment, $config);
            } catch (Throwable $exception) {
                Log::error(static::class.': Failed to auto-capture payment', [
                    'event' => $event,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}

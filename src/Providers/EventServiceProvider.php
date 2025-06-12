<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Providers;

use Bilberry\PaymentGateway\Events\ExternalPaymentEvent;
use Bilberry\PaymentGateway\Events\PaymentEvent;
use Bilberry\PaymentGateway\Events\RefundEvent;
use Bilberry\PaymentGateway\Listeners\ExternalPaymentEventListener;
use Bilberry\PaymentGateway\Listeners\PaymentEventListener;
use Bilberry\PaymentGateway\Listeners\RefundEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PaymentEvent::class => [
            PaymentEventListener::class,
        ],
        RefundEvent::class => [
            RefundEventListener::class,
        ],
        ExternalPaymentEvent::class => [
            ExternalPaymentEventListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}

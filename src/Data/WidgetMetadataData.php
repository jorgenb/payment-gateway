<?php

namespace Bilberry\PaymentGateway\Data;

use Spatie\LaravelData\Data;

final class WidgetMetadataData extends Data
{
    public function __construct(
        /**
         * Metadata returned to the frontend for initializing a payment widget.
         * This structure should be extended if more fields are needed for widget initialization.
         *
         * @property string $clientKey The client key used for client-side SDK initialization, e.g., Adyen's clientKey.
         * @property string|null $sessionId The session ID used for Adyen Sessions API initialization.
         * @property string|null $sessionData The session data token used for Adyen Sessions API initialization.
         * @property string|null $clientSecret The client secret used for client-side operations, e.g., Stripe's client secret.
         */
        public readonly string $clientKey,
        public readonly ?string $sessionId = null,
        public readonly ?string $sessionData = null,
        public readonly ?string $clientSecret = null,
    ) {}
}

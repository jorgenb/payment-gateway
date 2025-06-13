<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

/**
 * Configuration object used to initialize and configure payment providers.
 *
 * This object is designed to be generic and consistent across all supported payment providers.
 * The consuming application is responsible for providing these values when initializing
 * a payment provider.
 *
 * @property string $context_id Arbitrary context reference for config resolution, set by the consuming application.
 */
final class PaymentProviderConfig extends Data
{
    /**
     * @param  string  $context_id
     *                              Arbitrary context reference for config resolution, set by the consuming application.
     * @param  string  $apiKey
     *                          The API key used to configure the provider client.
     * @param  string  $environment
     *                               The environment in which the payment provider operates (e.g., 'live', 'test').
     * @param  string|null  $merchantAccount
     *                                        The merchant account identifier.
     * @param  string|null  $termsUrl
     *                                 The URL to terms and conditions.
     * @param  string|null  $redirectUrl
     *                                    The URL where the customer should be redirected after payment.
     * @param  string|null  $webhookSigningSecret
     *                                             The secret used to verify incoming webhook signatures from the payment provider.
     */
    public function __construct(
        #[Required, StringType]
        public readonly string $context_id,
        #[Required, StringType]
        public readonly string $apiKey,
        #[Required, StringType, In(['live', 'test'])]
        public readonly string $environment,
        #[StringType]
        public readonly ?string $merchantAccount = null,
        #[StringType, Url]
        public readonly ?string $termsUrl = null,
        #[StringType, Url]
        public readonly ?string $redirectUrl = null,
        #[StringType]
        public readonly ?string $webhookSigningSecret = null,
    ) {}
}

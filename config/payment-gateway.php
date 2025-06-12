<?php

// config for Bilberry/PaymentGateway
return [
    'nets' => [
        /** @phpstan-ignore-next-line */
        'base_url' => env('NETS_BASE_URL', 'https://test.api.dibspayment.eu'),
        /** @phpstan-ignore-next-line */
        'secret' => env('NETS_SECRET'),
        /** @phpstan-ignore-next-line */
        'verify_signature' => env('NETS_VERIFY_WEBHOOK', true),
        /** @phpstan-ignore-next-line */
        'nets_merchant' => env('NETS_MERCHANT'),
        'checkout' => [
            /** @phpstan-ignore-next-line */
            'url' => env('NETS_CHECKOUT_URL', 'https://example.com/checkout'),
            /** @phpstan-ignore-next-line */
            'terms_url' => env('NETS_TERMS_URL', 'https://example.com/terms'),
            /** @phpstan-ignore-next-line */
            'key' => env('NETS_CHECKOUT_KEY'),
        ],
        /** @phpstan-ignore-next-line */
        'webhook_secret' => env('NETS_WEBHOOK_SECRET'),
        'quick_checkout' => true,
    ],
    'stripe' => [
        /** @phpstan-ignore-next-line */
        'public' => env('STRIPE_PUBLIC'),
        /** @phpstan-ignore-next-line */
        'secret' => env('STRIPE_SECRET'),
        /** @phpstan-ignore-next-line */
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
        /** @phpstan-ignore-next-line */
        'success_url' => env('STRIPE_SUCCESS_URL', 'https://example.com/success'),
        /** @phpstan-ignore-next-line */
        'cancel_url' => env('STRIPE_CANCEL_URL', 'https://example.com/cancel'),
        /** @phpstan-ignore-next-line */
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'adyen' => [
        /** @phpstan-ignore-next-line */
        'base_url' => env('ADYEN_BASE_URL', 'https://checkout-test.adyen.com/v69'),
        /** @phpstan-ignore-next-line */
        'api_key' => env('ADYEN_API_KEY'),
        /** @phpstan-ignore-next-line */
        'management_api_key' => env('ADYEN_MANAGEMENT_API_KEY'),
        /** @phpstan-ignore-next-line */
        'merchant_account' => env('ADYEN_MERCHANT_ACCOUNT'),
        /** @phpstan-ignore-next-line */
        'webhook_secret' => env('ADYEN_WEBHOOK_SECRET'),
        /** @phpstan-ignore-next-line */
        'return_url' => env('ADYEN_RETURN_URL', 'https://example.com/return'),
        /** @phpstan-ignore-next-line */
        'cancel_url' => env('ADYEN_CANCEL_URL', 'https://example.com/cancel'),
        /** @phpstan-ignore-next-line */
        'success_url' => env('ADYEN_SUCCESS_URL', 'https://example.com/success'),
    ],
];

<?php

declare(strict_types=1);

dataset('stripe callback requests', [
    'payment_intent.created' => fn () => [
        'id' => 'evt_1N1xTx2eZvKYlo2CZVhKxk0Z',
        'type' => 'payment_intent.created',
        'created' => 1672531200,
        'data' => [
            'object' => [
                'id' => 'pi_3N1xTv2eZvKYlo2C1NwHEznq',
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'requires_payment_method',
                'metadata' => [
                    'merchantReference' => '01jv28c0pa75wms30pq2e5h8p3',
                ],
            ],
        ],
    ],

    'payment_intent.succeeded' => fn () => [
        'id' => 'evt_1N1xTx2eZvKYlo2C4UEfWwhF',
        'type' => 'payment_intent.succeeded',
        'created' => 1672531210,
        'data' => [
            'object' => [
                'id' => 'pi_3N1xTv2eZvKYlo2C1NwHEznq',
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'succeeded',
                'charges' => [
                    'data' => [
                        [
                            'id' => 'ch_3N1xTz2eZvKYlo2C1szIQJ7U',
                            'amount' => 1000,
                            'status' => 'succeeded',
                        ],
                    ],
                ],
                'metadata' => [
                    'merchantReference' => '01jv28c0pa75wms30pq2e5h8p3',
                ],
            ],
        ],
    ],

    'payment_intent.payment_failed' => fn () => [
        'id' => 'evt_1N1xTx2eZvKYlo2CcRYrkE1q',
        'type' => 'payment_intent.payment_failed',
        'created' => 1672531220,
        'data' => [
            'object' => [
                'id' => 'pi_3N1xTv2eZvKYlo2C1NwHEznq',
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'requires_payment_method',
                'last_payment_error' => [
                    'message' => 'Your card was declined.',
                ],
                'metadata' => [
                    'merchantReference' => '01jv28c0pa75wms30pq2e5h8p3',
                ],
            ],
        ],
    ],
]);

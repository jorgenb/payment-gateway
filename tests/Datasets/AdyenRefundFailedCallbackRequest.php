<?php

declare(strict_types=1);

dataset('adyen refund failed callback request', [
    'payment refund failed' => fn () => [
        'live'              => 'false',
        'notificationItems' => [
            [
                'NotificationRequestItem' => [
                    'additionalData' => [
                        'hmacSignature'        => 'b0ea55c2fe60d4d1d605e9c385e0e7...',
                        'paymentMethodVariant' => 'blik',
                    ],
                    'amount' => [
                        'currency' => 'NOK',
                        'value'    => 1000,
                    ],
                    'eventCode'           => 'REFUND_FAILED',
                    'eventDate'           => '2021-01-01T01:00:00+01:00',
                    'merchantAccountCode' => 'YOUR_MERCHANT_ACCOUNT',
                    'merchantReference'   => Str::uuid()->toString(),
                    'originalReference'   => '9913140798220028',
                    'paymentMethod'       => 'blik',
                    'pspReference'        => 'QFQTPCQ8HXSKGK82',
                    'reason'              => 'Refund Failed',
                    'success'             => 'true',
                ],
            ],
        ],
    ],
]);

<?php

declare(strict_types=1);

dataset('adyen callback requests', [
    'AUTHORISATION' => fn () => [
        'live'              => 'false',
        'notificationItems' => [
            [
                'NotificationRequestItem' => [
                    'additionalData' => [
                        'expiryDate'              => '03/2030',
                        'authCode'                => '018380',
                        'cardSummary'             => '1111',
                        'threeds2.cardEnrolled'   => 'false',
                        'checkoutSessionId'       => 'CSDFD5C8CA76096256',
                        'checkout.cardAddedBrand' => 'visa',
                        'hmacSignature'           => 'fl6xsPSQvp+ALO8Tjmb8FOA9T9Qz6JMqDGTWoZp//mY=',
                    ],
                    'amount' => [
                        'currency' => 'NOK',
                        'value'    => 33600,
                    ],
                    'eventCode'           => 'AUTHORISATION',
                    'eventDate'           => '2025-05-07T07:13:06+02:00',
                    'merchantAccountCode' => 'BilberryECOM',
                    'merchantReference'   => '01jtmjj9va8pxdsgdkaravgchx',
                    'operations'          => ['CANCEL', 'CAPTURE', 'REFUND'],
                    'paymentMethod'       => 'visa',
                    'pspReference'        => 'CL99KFLJT3QN2S65',
                    'reason'              => '018380:1111:03/2030',
                    'success'             => 'true',
                ],
            ],
        ],
    ],
]);

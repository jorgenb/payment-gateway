<?php

declare(strict_types=1);

dataset('nets payment reservation created', fn () => [
    [[
        'id' => '924bc362374949dba0dbc11131d88487',
        'event' => 'payment.reservation.created',
        'timestamp' => '2024-11-06T19:02:21.0787+00:00',
        'merchantId' => 100242833,
        'merchantNumber' => 0,
        'data' => [
            'cardDetails' => [
                'creditDebitIndicator' => 'C',
                'expiryMonth' => '12',
                'expiryYear' => '28',
                'issuerCountry' => 'NO',
                'truncatedPan' => '374500*****1009',
                'threeDSecure' => [
                    'acsUrl' => 'https://acs.example.com',
                    'authenticationEnrollmentStatus' => 'Y',
                    'authenticationStatus' => 'Y',
                    'eci' => '05',
                ],
            ],
            'paymentMethod' => null,
            'paymentType' => null,
            'consumer' => [
                'billingAddress' => [
                    'addressLine1' => 'Strandvejen 56',
                    'addressLine2' => '29/11',
                    'city' => 'Copenhagen',
                    'country' => 'Denmark',
                    'postcode' => '1050',
                    'receiverLine' => 'Strandvejen 56, 29/11',
                ],
                'country' => 'Denmark',
                'email' => 'test@example.com',
                'ip' => '17.172.224.47',
                'merchantReference' => '1234567890',
                'phoneNumber' => [
                    'prefix' => '+47',
                    'number' => '123456789',
                ],
                'shippingAddress' => [
                    'addressLine1' => 'Strandvejen 56',
                    'addressLine2' => '29/11',
                    'city' => 'Copenhagen',
                    'country' => 'Denmark',
                    'postcode' => '1050',
                    'receiverLine' => 'Strandvejen 56, 29/11',
                ],
            ],
            'reservationReference' => null,
            'reserveId' => null,
            'myReference' => \Illuminate\Support\Str::uuid()->toString(),
            'reconciliationReference' => 'MRJhJvEDCx1y7uWlKfb6O3z78',
            'amount' => [
                'amount' => '10000',
                'currency' => 'SEK',
            ],
            'paymentId' => 'b015690c89d141f7b98b99dee769be62',
        ]],
    ]]);

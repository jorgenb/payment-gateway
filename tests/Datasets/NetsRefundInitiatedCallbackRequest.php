<?php

declare(strict_types=1);

dataset('nets refund initiated callback request', [
    'payment refund initiated' => fn () => [
        'id'             => '126c939888c34cd09deae1a39e2bef20',
        'event'          => 'payment.refund.initiated',
        'timestamp'      => '2024-11-06T19:02:21.0786+00:00',
        'merchantId'     => 100242833,
        'merchantNumber' => 0,
        'data'           => [
            'refundId'   => '32e1cb8de6704c4baf9974121cc1351f',
            'chargeId'   => '55a8e4e3d0394353b7b51a9137c6e720',
            'orderItems' => [
                [
                    'grossTotalAmount' => '10000',
                    'name'             => 'Product 1',
                    'netTotalAmount'   => '8000',
                    'quantity'         => '2',
                    'reference'        => 'Red shoe 12',
                    'taxRate'          => '20',
                    'taxAmount'        => '2000',
                    'unit'             => 'pcs',
                    'unitPrice'        => '4000',
                ],
            ],
            'myReference' => Str::uuid()->toString(),
            'amount'      => [
                'amount'   => '10000',
                'currency' => 'SEK',
            ],
            'paymentId' => 'b015690c89d141f7b98b99dee769be62',
        ],
    ],
]);

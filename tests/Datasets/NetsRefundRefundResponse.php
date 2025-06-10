<?php

declare(strict_types=1);

dataset('nets refund completed callback request', [
    'payment refund completed' => fn () => [
        'id'             => 'a4d0bfd84a024f168b67ff87af8c821c',
        'event'          => 'payment.refund.completed',
        'timestamp'      => '2024-11-06T19:02:21.0785+00:00',
        'merchantId'     => 100242833,
        'merchantNumber' => 0,
        'data'           => [
            'refundId'       => '32e1cb8de6704c4baf9974121cc1351f',
            'invoiceDetails' => [
                'accountNumber'    => '1234567890',
                'distributionType' => 'Email',
                'invoiceDueDate'   => '2024-12-31',
                'invoiceNumber'    => '1234567890',
                'ocrOrkid'         => '1234567890',
                'ourReference'     => '1234567890',
                'yourReference'    => '9876543210',
            ],
            'reconciliationReference' => 'MRJhJvEDCx1y7uWlKfb6O3z78',
            'amount'                  => [
                'amount'   => '10000',
                'currency' => 'NOK',
            ],
            'paymentId'   => 'b015690c89d141f7b98b99dee769be62',
            'myReference' => Str::uuid()->toString(),
        ],
    ]
]);

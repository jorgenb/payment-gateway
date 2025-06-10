<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Tests\Support;

use Bilberry\PaymentGateway\Http\Requests\NetsChargePaymentRequest;
use Bilberry\PaymentGateway\Http\Requests\NetsCreatePaymentRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Faking\MockClient;

trait MocksNetsPayments
{
    protected function mockNetsSuccessfulPayment(string $paymentId): void
    {
        MockClient::global([
            NetsCreatePaymentRequest::class => MockResponse::make([
                'paymentId'            => $paymentId,
                'hostedPaymentPageUrl' => "https://test.nets.eu/payments/{$paymentId}",
            ], 201),
        ]);
    }

    protected function mockNetsSuccessfulCharge(string $paymentId): void
    {
        MockClient::global([
            NetsChargePaymentRequest::class => MockResponse::make([
                'chargeId' => '55a8e4e3d0394353b7b51a9137c6e720',
            ], 200),
        ]);
    }

    protected function mockNetsSuccessfulRefundInitiated(string $refundId): void
    {
        MockClient::global([
            NetsCreatePaymentRequest::class => MockResponse::make([
                'refundId' => $refundId,
            ], 201),
        ]);
    }

    protected function mockNetsFailedPayment400Status(): void
    {
        MockClient::global([
            NetsCreatePaymentRequest::class => MockResponse::make([
                'errors' => [
                    'property1' => 'error1',
                    'property2' => 'error2'
                ],
            ], 400),
        ]);
    }

    protected function mockNetsFailedPayment500Status(): void
    {
        MockClient::global([
            NetsCreatePaymentRequest::class => MockResponse::make([
                'message' => 'Server Error',
                'code'    => '500',
                'source'  => 'internal',
            ], 500),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Bilberry\PaymentGateway\Data\PaymentRefundData;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\PaymentGateway;
use Throwable;

class RefundsController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway
    ) {}

    /**
     * Initiates a refund of a charge using a specified provider.
     *
     * The client must provide a "provider" along with other payment details.
     * For example, 'nets', 'stripe', or 'adyen'.
     *
     * The payment ID is always used as the reference when initiating the refund request using this API.
     *
     * Note: The refund reason is currently not implemented and forwarded to the payment provider.
     */
    public function store(PaymentRefundData $data): RefundResponse
    {
        try {
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $provider = PaymentProvider::tryFrom($data->provider);
            $config = $configResolver->resolve($provider);

            $refund = PaymentRefund::create([
                'payment_id' => $data->payment_id,
                'amount_minor' => $data->amount_minor,
                'currency' => $data->currency,
            ]);

            $response = $this->gateway->refund($refund, $config);
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }

        return $response;
    }
}

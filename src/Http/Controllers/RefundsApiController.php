<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Bilberry\PaymentGateway\Data\PaymentRefundData;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\Services\PaymentGateway;
use Throwable;

class RefundsApiController extends Controller
{
    /**
     * Initiates a refund of a charge using a specified provider.
     *
     * The client must provide a "provider" along with other payment details.
     * For example, 'nets', 'stripe', or 'adyen'.
     *
     * The payment ID is always used as the reference when initiating the refund request using this API.
     *
     * Note: The refund reason is currently not implemented and forwarded to the payment provider.
     *
     * @param  PaymentRefundData  $data
     * @return RefundResponse
     */
    public function store(PaymentRefundData $data): RefundResponse
    {
        try {
            $providerInstance = PaymentGateway::getProvider(PaymentProvider::tryFrom($data->provider));
            $refund = PaymentRefund::create([
                'payment_id' => $data->payment_id,
                'amount_minor' => $data->amount_minor,
                'currency' => $data->currency,
            ]);
            $response = $providerInstance->refund($refund);
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }

        return $response;
    }
}

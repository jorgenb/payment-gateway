<?php

namespace Bilberry\PaymentGateway;

use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\RefundResponse;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\Providers\AdyenPaymentProvider;
use Bilberry\PaymentGateway\Providers\NetsPaymentProvider;
use Bilberry\PaymentGateway\Providers\StripePaymentProvider;
use Throwable;

class PaymentGateway
{
    /**
     * Create and initiate a payment.
     */
    public function create(
        PaymentRequestData $data,
        PaymentProviderConfig $config
    ): PaymentResponse {

        try {
            $payment = Payment::create([
                'payable_id' => $data->payable_id,
                'payable_type' => $data->payable_type,
                'amount_minor' => $data->amount_minor,
                'currency' => $data->currency,
                'provider' => $data->provider,
                'status' => PaymentStatus::PENDING,
                'capture_at' => $data->capture_at,
                'auto_capture' => $data->auto_capture,
                'context_id' => $config->context_id,
            ]);

            $provider = $this->getProvider(PaymentProvider::tryFrom($data->provider));

            return $provider->initiate($payment, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Cancels a reserved payment before it is charged.
     */
    public function cancel(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        try {
            $provider = $this->getProvider($payment->provider);

            return $provider->cancel($payment, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Charges a previously reserved payment.
     */
    public function charge(Payment $payment, PaymentProviderConfig $config): PaymentResponse
    {
        try {
            $provider = $this->getProvider($payment->provider);

            return $provider->charge($payment, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Initiates a refund for a charged payment.
     */
    public function refund(
        PaymentRefund $refund,
        PaymentProviderConfig $config
    ): RefundResponse {
        try {
            $provider = $this->getProvider($refund->payment->provider);

            return $provider->refund($refund, $config);
        } catch (\Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Returns an instance of a payment provider based on the provided provider enum.
     *
     * @param  PaymentProvider  $provider  The payment provider enum instance.
     */
    public static function getProvider(PaymentProvider $provider): NetsPaymentProvider|StripePaymentProvider|AdyenPaymentProvider
    {
        return match ($provider) {
            PaymentProvider::NETS => app(NetsPaymentProvider::class),
            PaymentProvider::STRIPE => app(StripePaymentProvider::class),
            PaymentProvider::ADYEN => app(AdyenPaymentProvider::class),
        };
    }
}

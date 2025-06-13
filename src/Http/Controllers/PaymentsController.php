<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Bilberry\PaymentGateway\Data\PaymentCancelData;
use Bilberry\PaymentGateway\Data\PaymentChargeData;
use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\Resources\ShowPaymentResourceData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\PaymentGateway;
use Throwable;

class PaymentsController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway
    ) {}

    /**
     * Initiates a payment using a specified provider.
     */
    public function store(PaymentRequestData $data): PaymentResponse
    {
        try {
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve(PaymentProvider::tryFrom($data->provider));

            return $this->gateway->create($data, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Cancels a reserved payment before it is charged.
     */
    public function cancel(PaymentCancelData $data): PaymentResponse
    {
        try {
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve($data->provider);
            $payment = Payment::findOrFail($data->paymentId);

            return $this->gateway->cancel($payment, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Charges a previously reserved payment.
     */
    public function charge(PaymentChargeData $data): PaymentResponse
    {
        try {
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve($data->provider);
            $payment = Payment::findOrFail($data->paymentId);

            return $this->gateway->charge($payment, $config);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Display the specified payment resource.
     */
    public function show(string $id): ShowPaymentResourceData
    {
        $payment = Payment::findOrFail($id);

        return ShowPaymentResourceData::from($payment);
    }
}

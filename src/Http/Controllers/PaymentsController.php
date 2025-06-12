<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Bilberry\PaymentGateway\Data\PaymentCancelData;
use Bilberry\PaymentGateway\Data\PaymentChargeData;
use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Data\PaymentResponse;
use Bilberry\PaymentGateway\Data\Resources\ShowPaymentResourceData;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Services\PaymentGateway;
use Throwable;

class PaymentsController extends Controller
{
    public function __construct(
        private readonly ?PaymentProviderInterface $provider = null
    ) {}

    /**
     * Initiates a payment using a specified provider.
     *
     * The client must provide a "provider" along with other payment details.
     * For example, 'nets', 'stripe', or 'adyen'.
     *
     * The `payable_id` and `payable_type` form a polymorphic relation.
     *
     *  - `payable_id` refers to the ID of the associated payable entity.
     *  - `payable_type` refers to the fully qualified class name of the associated payable model (e.g., \Models\Invoice).
     *
     *  This allows payments to be associated with different domain models such as invoices, orders, or subscriptions.
     *
     *  The `capture_at` and `auto_capture` fields determine how, and when the payment is captured:
     *  - If `capture_at` is provided, auto-capture is disabled and the payment will NOT be captured automatically.
     *  - In this case, it is the responsibility of the API consumer to call the `charge` endpoint manually at the appropriate time.
     *  - If `capture_at` is not provided and `auto_capture` is true (default), the system will attempt to capture automatically (depending on provider behavior).
     */
    public function store(PaymentRequestData $data): PaymentResponse
    {
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
            ]);

            return $this->provider->initiate($payment);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Cancels a reserved payment before it is charged.
     */
    public function cancel(PaymentCancelData $request): PaymentResponse
    {
        try {
            $providerInstance = PaymentGateway::getProvider($request->provider);
            $payment = Payment::findOrFail($request->paymentId);

            return $providerInstance->cancel($payment);
        } catch (Throwable $exception) {
            report($exception);
            abort(400, $exception->getMessage());
        }
    }

    /**
     * Charges a previously reserved payment.
     */
    public function charge(PaymentChargeData $request): PaymentResponse
    {
        try {
            $providerInstance = PaymentGateway::getProvider($request->provider);
            $payment = Payment::findOrFail($request->paymentId);

            return $providerInstance->charge($payment);
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

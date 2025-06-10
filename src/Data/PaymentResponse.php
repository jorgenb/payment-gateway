<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\Payment;
use Spatie\LaravelData\Data;

/**
 * Data transfer object representing a response after handling a payment.
 */
class PaymentResponse extends Data
{
    public function __construct(
        /**
         * The final status of the payment after processing.
         * @example PaymentStatus::CHARGED
         */
        public readonly PaymentStatus $status,

        /**
         * The Payment model instance that was processed.
         * @example ['provider' => 'nets', 'currency' => 'NOK', 'amount_minor' => 33600, 'invoice_id' => '01JRANNKTC4FANVYW858952GK8', 'capture_at' => null, 'id' => '01jrfv1wx0v9qknd356s2n12mt', 'updated_at' => '2025-04-10T12:33:32.000000Z', 'created_at' => '2025-04-10T12:33:32.000000Z', 'external_id' => 'ca6f754f16ae4c759765c921007b3e68', 'total_charged_amount' => ['amount' => '336.00', 'currency' => 'NOK'], 'total_refunded_amount' => ['amount' => '0.00', 'currency' => 'NOK'], 'refunds' => []]
         */
        public readonly Payment $payment,

        /**
         * Raw response data returned from the payment provider.
         * @example ['charge_id' => 'abc123', 'status' => 'succeeded']
         */
        public readonly array $responseData,

        /**
         * Optional metadata returned alongside the response.
         * Can include provider-specific keys, e.g. Stripe client secret for frontend use.
         * @example ['clientSecret' => 'cs_test_1234']
         */
        public readonly ?array $metadata = null,
    ) {
    }
}

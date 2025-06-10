<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Data;

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Spatie\LaravelData\Data;

/**
 * Data transfer object representing a response after handling a refund.
 */
class RefundResponse extends Data
{
    public function __construct(
        /**
         * The final status of the refund after processing.
         * @example PaymentStatus::REFUNDED
         */
        public readonly PaymentStatus $status,

        /**
         * The PaymentRefund model instance that was processed.
         * @example ['id' => '01HW9AZRWYRE31W6T7RC8VDXYF', 'payment_id' => '01HW9AZRWMKZHPWJ6VYXW6RM73', 'amount_minor' => 33600, 'currency' => 'NOK', 'status' => 'refunded', 'external_refund_id' => 'ext_ref_123', 'metadata' => ['note' => 'partial']]
         */
        public readonly PaymentRefund $refund,

        /**
         * Raw response data returned from the payment provider.
         * @example ['id' => 'abc-123', 'status' => 'refunded']
         */
        public readonly array $responseData,

        /**
         * Optional metadata returned alongside the response.
         * Can include provider-specific details.
         * @example ['note' => 'Handled by provider']
         */
        public readonly ?array $metadata = null,
    ) {
    }
}

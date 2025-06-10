<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use JsonException;
use Bilberry\PaymentGateway\Data\NetsPaymentRefundResponseData;
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class NetsRefundChargeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly PaymentRefund $refund,
    ) {
    }

    /**
     * Resolve the endpoint for refunding a payment.
     */
    public function resolveEndpoint(): string
    {
        return '/v1/charges/'.$this->refund->payment->external_charge_id.'/refunds';
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): NetsPaymentRefundResponseData
    {
        $data = $response->json();

        return new NetsPaymentRefundResponseData(
            refundId: $data['refundId'],
            rawPayload: $data,
        );
    }

    /**
     * Define the default body for the refund request.
     */
    public function defaultBody(): array
    {
        return [
            'myReference' => $this->refund->payment_id,
            'amount'      => $this->refund->amount_minor,
            'orderItems'  => [
                [
                    'reference'        => $this->refund->payment_id,
                    'name'             => 'Refund',
                    'quantity'         => 1,
                    'unit'             => 'pcs',
                    'unitPrice'        => $this->refund->amount_minor,
                    'taxRate'          => 0,
                    'taxAmount'        => 0,
                    'grossTotalAmount' => $this->refund->amount_minor,
                    'netTotalAmount'   => $this->refund->amount_minor,
                ],
            ],
        ];
    }
}

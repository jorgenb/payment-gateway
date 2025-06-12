<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use Bilberry\PaymentGateway\Data\NetsPaymentChargeResponseData;
use Bilberry\PaymentGateway\Models\Payment;
use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class NetsChargePaymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected readonly Payment $payment) {}

    /**
     * Resolve the endpoint for charging a payment.
     */
    public function resolveEndpoint(): string
    {
        return '/v1/payments/'.$this->payment->external_id.'/charges';
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): NetsPaymentChargeResponseData
    {
        $data = $response->json();

        return new NetsPaymentChargeResponseData(
            chargeId: $data['chargeId'],
            rawPayload: $data,
        );
    }

    /**
     * Define the default body for the charge request.
     */
    public function defaultBody(): array
    {
        return [
            'amount' => $this->payment->amount_minor,
            'orderItems' => [],
            'myReference' => $this->payment->id,
        ];
    }
}

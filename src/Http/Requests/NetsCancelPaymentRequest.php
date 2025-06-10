<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use Bilberry\PaymentGateway\Models\Payment;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class NetsCancelPaymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected readonly Payment $payment)
    {
    }

    /**
     * Resolve the endpoint for canceling a payment.
     */
    public function resolveEndpoint(): string
    {
        return '/v1/payments/'.$this->payment->external_id.'/cancels';
    }

    /**
     * Define the default body for the cancel request.
     */
    public function defaultBody(): array
    {
        $body = [
            'amount' => $this->payment->amount_minor,
        ];

        return $body;
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RetrieveNetsPayment extends Request
{
    public function __construct(
        protected readonly string $netsPaymentId
    ) {}

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/v1/payments/'.$this->netsPaymentId;
    }
}

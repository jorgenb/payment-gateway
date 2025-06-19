<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Connectors;

use Illuminate\Support\Str;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class NetsConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    protected string $apiKey;

    protected string $merchantAccount;

    /**
     * NetsConnector constructor.
     */
    public function __construct(string $apiKey, string $merchantAccount)
    {
        $this->apiKey = $apiKey;
        $this->merchantAccount = $merchantAccount;
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        $env = app()->environment();
        if (in_array($env, ['local', 'testing', 'staging'])) {
            return 'https://test.api.dibspayment.eu';
        }

        return 'https://api.dibspayment.eu';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }

    /**
     * Override the send method to automatically add an Idempotency-Key header
     * for all POST requests if one is not already present.
     */
    public function send(
        Request $request,
        ?MockClient $mockClient = null,
        ?callable $handleRetry = null
    ): Response {

        if ($request->getMethod() === Method::POST && $request->headers()->get('Idempotency-Key') === null) {
            $request->headers()->add('Idempotency-Key', $this->generateIdempotencyKey());
        }

        return parent::send($request, $mockClient, $handleRetry);
    }

    /**
     * Generate an idempotency key for POST requests.
     */
    public function generateIdempotencyKey(?string $key = null): string
    {
        return $key ?? Str::uuid()->toString();
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Connectors;

use Illuminate\Support\Str;
use InvalidArgumentException;
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

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return config('services.nets.base_url');
    }

    protected function defaultHeaders(): array
    {
        $secret = config('services.nets.secret');

        if ( ! is_string($secret) || empty($secret)) {
            throw new InvalidArgumentException('Nets secret is not configured properly.');
        }
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$secret,
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

        if (Method::POST === $request->getMethod() && null === $request->headers()->get('Idempotency-Key')) {
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

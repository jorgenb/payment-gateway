<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function response;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly ?PaymentProviderInterface $provider = null
    ) {}

    public function handleCallback(PaymentCallbackData $data, string $provider): Response
    {
        if (! $data->hasMerchantReference()) {
            Log::warning("Missing merchant reference for provider: {$provider}");
        }

        if (! $data->hasExternalId()) {
            Log::warning("Missing external ID for provider: {$provider}");
        }

        if ($data->isEventSupported()) {
            try {
                $this->provider->handleCallback($data);
            } catch (Throwable $exception) {
                Log::error("Error handling callback for provider: {$provider}", [
                    'exception' => $exception,
                    'data' => (array) $data,
                ]);

                return response()->noContent()->setStatusCode(202);
            }
        }

        return response()->noContent()->setStatusCode(202);
    }
}

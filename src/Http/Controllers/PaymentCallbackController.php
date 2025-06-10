<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Controllers;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Bilberry\PaymentGateway\Data\PaymentCallbackData;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function response;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentProviderInterface $provider
    ) {
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function handleCallback(Request $request, string $provider): Response
    {
        $data = PaymentCallbackData::fromRequest($request);

        if ( ! $data->hasMerchantReference()) {
            Log::warning("Missing merchant reference for provider: {$provider}");
        }

        if ( ! $data->hasExternalId()) {
            Log::warning("Missing external ID for provider: {$provider}");
        }

        if ($data->isEventSupported()) {
            try {
                $this->provider->handleCallback($data);
            } catch (Throwable $exception) {
                Log::error("Error handling callback for provider: {$provider}", [
                    'exception' => $exception,
                    'data'      => (array) $data,
                ]);
                return response()->noContent()->setStatusCode(202);
            }

        }

        return response()->noContent()->setStatusCode(202);
    }
}

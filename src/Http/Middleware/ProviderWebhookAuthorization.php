<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Middleware;

use Adyen\AdyenException;
use Adyen\Util\HmacSignature;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class ProviderWebhookAuthorization
{
    /**
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        $provider = PaymentProvider::tryFrom($request->route('provider'));

        if ( ! $provider) {
            return response()->json(
                ['error' => 'Invalid payment provider'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $secret = config("services.{$provider->value}.webhook_secret");

        $isAuthorized = match ($provider) {
            PaymentProvider::NETS   => $this->authorizeNets($request, $secret),
            PaymentProvider::STRIPE => $this->authorizeStripe($request, $secret),
            PaymentProvider::ADYEN  => $this->authorizeAdyen($request, $secret),
        };

        if ( ! $isAuthorized) {
            return response()->json(
                ['error' => 'Unauthorized webhook request'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }

    private function authorizeNets(Request $request, string $secret): bool
    {
        $authHeader = $request->header('Authorization');

        return $authHeader === $secret;
    }

    private function authorizeStripe(Request $request, string $secret): bool
    {
        $signature = $request->header('Stripe-Signature');
        if ( ! $signature) {
            return false;
        }

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret
            );
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @throws AdyenException
     */
    private function authorizeAdyen(Request $request, string $secret): bool
    {
        $validator = new HmacSignature();
        $notifications = $request->json()->all();

        if (isset($notifications['notificationItems'])) {
            $notificationItems = $notifications['notificationItems'];

            // Fetch the first (and only) NotificationRequestItem
            $item = array_shift($notificationItems);

            if (isset($item['NotificationRequestItem'])) {
                $requestItem = $item['NotificationRequestItem'];

                return $validator->isValidNotificationHMAC($secret, $requestItem);
            }

            return false;
        }

        return false;
    }
}

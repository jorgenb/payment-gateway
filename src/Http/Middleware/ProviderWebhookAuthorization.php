<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Middleware;

use Adyen\AdyenException;
use Adyen\Util\HmacSignature;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Interfaces\PaymentProviderConfigResolverInterface;
use Bilberry\PaymentGateway\Models\Payment;
use Closure;
use Exception;
use Illuminate\Http\Request;
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

        if (! $provider) {
            return response()->json(
                ['error' => 'Invalid payment provider'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($provider === PaymentProvider::NETS) {
            $merchantReference = $request->json('data.myReference');
            if ($merchantReference) {
                $contextId = Payment::query()->whereKey($merchantReference)->value('context_id');
            } else {
                $paymentId = $request->json('data.paymentId');
                $contextId = Payment::query()->where('external_id', $paymentId)->value('context_id');
            }

            if (! $contextId) {
                return response()->json(
                    ['error' => 'Payment not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve(
                $provider,
                $contextId
            );
            $secret = $config->webhookSigningSecret;
        } elseif ($provider === PaymentProvider::ADYEN) {
            // Extract merchantReference from Adyen webhook payload
            $merchantReference = $request->json('notificationItems.0.NotificationRequestItem.merchantReference');
            // TODO: handle multi-payment/edge-case if needed
            $contextId = Payment::query()->where('id', $merchantReference)->value('context_id');
            if (! $contextId) {
                return response()->json(
                    ['error' => 'Payment not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve(
                $provider,
                $contextId
            );
            $secret = $config->webhookSigningSecret;
        } elseif ($provider === PaymentProvider::STRIPE) {
            // Extract merchantReference from Stripe webhook payload
            $merchantReference = $request->json('data.object.metadata.merchantReference');
            $contextId = Payment::query()->where('id', $merchantReference)->value('context_id');
            if (! $contextId) {
                return response()->json(
                    ['error' => 'Payment not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            $configResolver = app(PaymentProviderConfigResolverInterface::class);
            $config = $configResolver->resolve(
                $provider,
                $contextId
            );

            $secret = $config->webhookSigningSecret;
        }

        $isAuthorized = match ($provider) {
            PaymentProvider::NETS => $this->authorizeNets($request, $secret),
            PaymentProvider::STRIPE => $this->authorizeStripe($request, $secret),
            PaymentProvider::ADYEN => $this->authorizeAdyen($request, $secret),
        };

        if (! $isAuthorized) {
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
        if (! $signature) {
            return false;
        }

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret
            );

            return true;
        } catch (Exception $exception) {
            report($exception);
            return false;
        }
    }

    /**
     * @throws AdyenException
     */
    private function authorizeAdyen(Request $request, string $secret): bool
    {
        $validator = new HmacSignature;
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

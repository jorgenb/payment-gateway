<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use Bilberry\PaymentGateway\Data\NetsPaymentResponseData;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Models\Payment;
use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class NetsCreatePaymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly Payment $payment,
        protected PaymentProviderConfig $providerConfig
    ) {}

    /**
     * Resolve the endpoint for creating a payment.
     */
    public function resolveEndpoint(): string
    {
        return '/v1/payments';
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): NetsPaymentResponseData
    {
        $data = $response->json();

        return new NetsPaymentResponseData(
            paymentId: $data['paymentId'],
            rawPayload: $data,
        );
    }

    public function defaultBody(): array
    {
        $webhookSigningSecret = $this->providerConfig->webhookSigningSecret;
        $callbackUrl = $this->getRoute();
        $quickCheckout = true; // TODO: Add to paymentproviderconfig

        $body = [
            'myReference' => $this->payment->id,
            'checkout' => [
                'url' => $this->providerConfig->redirectUrl,
                'termsUrl' => $this->providerConfig->termsUrl,
                'integrationType' => 'EmbeddedCheckout',
            ],
            'order' => [
                'amount' => $this->payment->amount_minor,
                'currency' => $this->payment->currency,
                'reference' => $this->payment->id,
                'items' => [
                    [
                        'reference' => $this->payment->id,
                        'name' => $this->payment->id,
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unitPrice' => $this->payment->amount_minor,
                        'taxRate' => 0,
                        'taxAmount' => 0,
                        'grossTotalAmount' => $this->payment->amount_minor,
                        'netTotalAmount' => $this->payment->amount_minor,
                    ],
                ],
            ],
            'notifications' => [
                'webhooks' => [ // https://developer.nexigroup.com/nexi-checkout/en-EU/api/webhooks/
                    [
                        'eventName' => 'payment.reservation.created',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.created',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.charge.created',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.reservation.failed',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.charge.failed',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.refund.initiated',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.refund.initiated.v2',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.refund.failed',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.refund.completed',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.cancel.created',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                    [
                        'eventName' => 'payment.cancel.failed',
                        'url' => $callbackUrl,
                        'authorization' => $webhookSigningSecret,
                    ],
                ],
            ],

        ];

        if ($quickCheckout) {
            $body['checkout']['merchantHandlesConsumerData'] = true;
        }

        return $body;
    }

    private function getRoute(): string
    {

        $relativeUrl = route('api.payments.callback', [PaymentProvider::NETS->value], false);

        return secure_url($relativeUrl);
    }
}

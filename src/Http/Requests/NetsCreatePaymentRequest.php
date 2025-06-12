<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Http\Requests;

use Bilberry\PaymentGateway\Data\NetsPaymentResponseData;
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
        protected readonly Payment $payment
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
        $secret = config('services.nets.webhook_secret'); // TODO: tenant specific config
        $callbackUrl = $this->getRoute();
        $quickCheckout = config('services.nets.quick_checkout');

        $body = [
            'myReference' => $this->payment->id,
            'checkout' => [
                'url' => config('services.nets.checkout.url'),
                'termsUrl' => config('services.nets.checkout.terms_url'),
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
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.created',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.charge.created',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.reservation.failed',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.charge.failed',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.refund.initiated',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.refund.initiated.v2',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.refund.failed',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.refund.completed',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.cancel.created',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
                    ],
                    [
                        'eventName' => 'payment.cancel.failed',
                        'url' => $callbackUrl,
                        'authorization' => $secret,
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

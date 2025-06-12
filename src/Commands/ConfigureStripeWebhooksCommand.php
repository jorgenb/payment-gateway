<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Commands;

use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Exception;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;

class ConfigureStripeWebhooksCommand extends Command
{
    protected $signature = 'stripe:webhook:setup';

    protected $description = 'Configure Stripe webhook endpoint for payment callbacks';

    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $endpoint = WebhookEndpoint::create([
                'url' => $this->getRoute(),
                'enabled_events' => [
                    'payment_intent.amount_capturable_updated',
                    'payment_intent.canceled',
                    'payment_intent.payment_failed',
                    'payment_intent.processing',
                    'payment_intent.requires_capture',
                    'payment_intent.succeeded',
                    'refund.created',
                    'refund.failed',
                ],
            ]);

            $this->info('Stripe webhook created successfully!');
            $this->line('Webhook ID: '.$endpoint->id);
            $this->line('Webhook URL: '.$endpoint->url);
            $this->line('You must store the webhook secret manually from the Stripe dashboard.');

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to create Stripe webhook: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function getRoute(): string
    {
        $relativeUrl = route('api.payments.callback', [PaymentProvider::STRIPE->value], false);

        return secure_url($relativeUrl);
    }
}

<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Commands;

use Illuminate\Console\Command;
use Adyen\Service\Management\WebhooksMerchantLevelApi;
use Adyen\Model\Management\CreateMerchantWebhookRequest;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Exception;

class ConfigureAdyenWebhooksCommand extends Command
{
    protected $signature = 'adyen:webhook:setup';
    protected $description = 'Configure webhook for Adyen Merchant and retrieve the HMAC key';

    public function handle(): int
    {
        $merchantAccount = config('services.adyen.merchant_account');
        $webhookUrl = $this->getRoute();

        /** @var WebhooksMerchantLevelApi $webhooksApi */
        $webhooksApi = app(WebhooksMerchantLevelApi::class);

        try {
            $request = new CreateMerchantWebhookRequest([
                'type'                => 'standard',
                'url'                 => $webhookUrl,
                'communicationFormat' => 'json',
                'sslVersion'          => 'TLSv1.2',
                'active'              => true,
            ]);

            $webhook = $webhooksApi->setUpWebhook($merchantAccount, $request);
            $webhookId = $webhook->getId();
            $hmacResponse = $webhooksApi->generateHmacKey($merchantAccount, $webhookId);
            $hmacKey = $hmacResponse->getHmacKey();

            if ($hmacKey) {
                $this->info('Webhook created successfully.');
                $this->line("HMAC Key: {$hmacKey}");
            } else {
                $this->error('Webhook created but HMAC key not returned.');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to create webhook: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function getRoute(): string
    {
        $relativeUrl = route('api.payments.callback', [PaymentProvider::ADYEN->value], false);
        return secure_url($relativeUrl);
    }
}

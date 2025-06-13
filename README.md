# This is my package payment-gateway

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adventure-tech/payment-gateway/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adventure-tech/payment-gateway/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/adventure-tech/payment-gateway/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/adventure-tech/payment-gateway/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require adventure-tech/payment-gateway
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="payment-gateway-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="payment-gateway-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="payment-gateway-views"
```


### Minimum Requirements

- PHP 8.2 or higher  
- Laravel 10.x or 11.x

## Configuration: Registering the PaymentProviderConfigResolver

- You **must** provide a `PaymentProviderConfig` with all necessary credentials for each payment provider operation you intend to use.
- The consuming app is free to implement advanced logic (per-tenant, per-user, dynamic environments, etc.).

> **Note:**  
> You must always configure API keys and webhook signing secrets for every payment provider you use, regardless of whether you use the package internally or via API routes.  
> Callback handling requires these secrets to be set up properly for all environments.

This package requires the **consuming application** to register an implementation of the `PaymentProviderConfigResolverInterface`. This interface is responsible for resolving and returning a `PaymentProviderConfig` object for a given provider (and, optionally, a tenant or context).

**Why?**  
This allows your app to define where and how payment provider credentials and settings are stored (e.g. in the database, tenant config, or environment variables).

### Example: Binding the Resolver in a Service Provider

In your `AppServiceProvider` (or any service provider), bind your resolver to the interface:

```php
$this->app->singleton(PaymentProviderConfigResolverInterface::class, function ($app) {
    return new class implements PaymentProviderConfigResolverInterface {
        public function resolve(PaymentProvider $provider, mixed $context = null): PaymentProviderConfig
        {
            // Implement your own logic here: retrieve credentials from DB, config based on the context.

            if ($provider === PaymentProvider::STRIPE) {
                return PaymentProviderConfig::from([
                    'context_id' => $context ?? 'tenant-id-or-user-id', // Required context ID for multi-tenant apps
                    'apiKey' => config('services.stripe.secret'),
                    'merchantAccount' => null,
                    'environment' => app()->environment('production') ? 'live' : 'test',
                    'termsUrl' => config('app.terms_url'),
                    'redirectUrl' => config('app.payment_redirect_url'),
                    'webhookSigningSecret' => config('services.stripe.webhook_secret'),
                ]);
            }

            // Handle other providers...
            throw new \RuntimeException("Provider [$provider->value] not supported.");
        }
    };
});
```

#### Where is this used?

- The `PaymentProviderConfigResolverInterface` **must always be bound in your application**.  
  This is required because callback and other asynchronous operations (such as async jobs) will always attempt to resolve provider configuration at runtime using this resolver, even if you primarily use the service or facade directly.
- When using the API routes provided by this package, the resolver is used to get provider config for payment, refund, and callback operations.
- When using the service or facade directly (internally), you can still provide a `PaymentProviderConfig` instance manually, but **the resolver binding is still required** to ensure callbacks and provider events work correctly.

## Permission Checks and Authorization

> **Note:**  
> Permission checks using [Laravel Gate/Policy hooks](https://laravel.com/docs/authorization#creating-policies) are **not yet implemented** in this package. This is a planned TODO. The consuming application will eventually need to define and register the appropriate policy (e.g., `PaymentPolicy`) in your application's `AuthServiceProvider`. When implemented, this policy will be responsible for business-specific permission logic for your users, tenants, or roles.

## Usage **examples**

The `PaymentGateway` facade provides a unified interface for creating, charging, refunding, and canceling payments across multiple providers.

### 1. Prepare the Provider Config

```php
use Bilberry\PaymentGateway\Facades\PaymentGateway;
use Bilberry\PaymentGateway\Data\PaymentProviderConfig;

$config = PaymentProviderConfig::from([
    'apiKey' => env('STRIPE_SECRET'),
    'environment' => 'test',
    'merchantAccount' => 'your-merchant-id',
    'termsUrl' => 'https://example.com/terms',
    'redirectUrl' => 'https://example.com/return',
    'webhookSigningSecret' => env('STRIPE_WEBHOOK_SECRET'),
]);
```

### 2. Initiate a Payment

```php
use Bilberry\PaymentGateway\Data\PaymentRequestData;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Facades\PaymentGateway;

// Build your DTO using ::from()
$paymentRequestData = PaymentRequestData::from([
    'provider' => PaymentProvider::STRIPE, // Payment provider enum
    'amount_minor' => 10000, // Amount in the smallest currency unit (e.g., øre, cents)
    'currency' => 'NOK', // ISO 4217 currency code
    'payable_id' => 'your-internal-id', // ID of the object being paid for (e.g., order ID)
    'payable_type' => 'order', // The type/name of the payable (e.g. 'order', lower case)
    'capture_at' => null, // (Optional) Datetime for delayed capture, or null for immediate
    'auto_capture' => true, // (Optional) Whether to auto-capture (true by default)
]);

$createResponse = PaymentGateway::create($paymentRequestData, $config);
```

### 3. Charge a Payment

```php
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Facades\PaymentGateway;

$payment = Payment::find($paymentId);

$chargeResponse = PaymentGateway::charge($payment, $config);
```

### 4. Initiate a Refund

```php
use Bilberry\PaymentGateway\Models\PaymentRefund;
use Bilberry\PaymentGateway\Facades\PaymentGateway;

$refund = PaymentRefund::find($refundId);

$refundResponse = PaymentGateway::refund($refund, $config);
```

### 5. Cancel a Payment

```php
use Bilberry\PaymentGateway\Models\Payment;
use Bilberry\PaymentGateway\Facades\PaymentGateway;

$payment = Payment::find($paymentId);

$cancelResponse = PaymentGateway::cancel($payment, $config);
```
Each method returns a data response object (`PaymentResponse`, `RefundResponse`, etc.) with details about the provider's response and the current state of the payment or refund.

For advanced use cases, consult the PHPDoc and source code in the `PaymentGateway` class.

## Callback Handling

This package provides a unified callback (webhook) endpoint for each supported payment provider. When a payment provider (such as Stripe, Adyen, or Nets) sends events to your application (e.g., for payment status updates, refunds, or chargebacks), these endpoints will receive and process those events automatically.

- **Endpoints:**  
  The package registers endpoints for each provider, such as:
  - `/api/payments/callback/stripe`
  - `/api/payments/callback/adyen`
  - `/api/payments/callback/nets`

- **Automatic Processing:**  
  All supported provider events are parsed and handled by the package, updating your payment and refund records as needed.

- **Security:**  
  Ensure you set the correct webhook or callback secret in your `PaymentProviderConfig` to validate incoming requests from the provider.

- **No Extra Work Needed:**  
  You do **not** need to write any additional controller logic for handling payment provider callbacks—this package takes care of it out of the box.

- **Customization:**  
  If you need to extend or react to callback events (for example, to trigger domain-specific events or notifications), you can listen for Laravel events that the package dispatches after processing each callback.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

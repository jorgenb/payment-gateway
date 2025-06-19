<?php

use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Controllers\PaymentsController;
use Bilberry\PaymentGateway\Http\Controllers\RefundsController;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function (): void {

    // Routes accessible by the payment widget
    Route::middleware(['payment-gateway-widget-key'])->group(function () {
        // TODO: Endpoint for terminating a payment session
    });

    // Routes that require higher privilege (e.g., admin, backend integration)
    Route::middleware('payment-gateway-client')->group(function () {
        Route::post('/payments/{payment}/charge', [PaymentsController::class, 'charge'])->name('api.payments.charge');
        Route::post('refunds', [RefundsController::class, 'store'])
            ->name('api.refunds.store');
        Route::get('/payments/{payment}', [PaymentsController::class, 'show'])
            ->name('api.payments.show');
        Route::post('/payments/{payment}/cancel', [PaymentsController::class, 'cancel'])
            ->name('api.payments.cancel');
        Route::post('payments', [PaymentsController::class, 'store'])
            ->name('api.payments.store');
    });

    // Callback routes for payment providers
    Route::middleware(ProviderWebhookAuthorization::class)->group(function () {
        Route::post('/payments/callback/{provider}', [PaymentCallbackController::class, 'handleCallback'])
            ->name('api.payments.callback');
    });
});

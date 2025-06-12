<?php

use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Controllers\PaymentsController;
use Bilberry\PaymentGateway\Http\Controllers\RefundsController;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Illuminate\Support\Facades\Route;

Route::middleware(['client'])->prefix('api/v1')->group(function (): void {
    Route::get('/payments/{payment}', [PaymentsController::class, 'show'])->name('api.payments.show');
    Route::post('payments', [PaymentsController::class, 'store'])->name('api.payments.store');
    Route::post('/payments/{payment}/cancel', [PaymentsController::class, 'cancel'])->name('api.payments.cancel');
    Route::post('/payments/{payment}/charge', [PaymentsController::class, 'charge'])->name('api.payments.charge');
    Route::post('refunds', [RefundsController::class, 'store'])->name('api.refunds.store');
});

Route::post('/payments/callback/{provider}', [PaymentCallbackController::class, 'handleCallback'])
    ->prefix('api/v1')
    ->name('api.payments.callback')
    ->middleware(ProviderWebhookAuthorization::class);

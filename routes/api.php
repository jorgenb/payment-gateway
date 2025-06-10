<?php

use Bilberry\PaymentGateway\Http\Controllers\PaymentCallbackController;
use Bilberry\PaymentGateway\Http\Controllers\PaymentsApiController;
use Bilberry\PaymentGateway\Http\Controllers\RefundsApiController;
use Bilberry\PaymentGateway\Http\Middleware\ProviderWebhookAuthorization;
use Illuminate\Support\Facades\Route;

//// TODO: figure out how to handle middleware
Route::middleware(['client'])->prefix('v1')->group(function (): void {
    Route::apiResource('payments', PaymentsApiController::class)->names('api.payments')->only(['store']);
    Route::get('/payments/{payment}', [PaymentsApiController::class, 'show'])->name('api.payments.show');
    Route::post('/payments/{payment}/cancel', [PaymentsApiController::class, 'cancel'])->name('api.payments.cancel');
    Route::post('/payments/{payment}/charge', [PaymentsApiController::class, 'charge'])->name('api.payments.charge');
    Route::apiResource('refunds', RefundsApiController::class)->names('api.refunds')->only(['store']);
});

Route::post('/payments/callback/{provider}', [PaymentCallbackController::class, 'handleCallback'])
    ->name('api.payments.callback')
    ->middleware(ProviderWebhookAuthorization::class);

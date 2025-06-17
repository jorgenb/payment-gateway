<?php

use Bilberry\PaymentGateway\Models\FakePayable;
use Bilberry\PaymentGateway\Models\Payment;
use Illuminate\Support\Facades\Route;

if (app()->environment(['local', 'testing', 'staging'])) {
    Route::get('/payment-gateway-testing', function () {

        $payables = FakePayable::latest()->get();
        $payments = Payment::with(['events'])->latest()->get();

        /** @phpstan-ignore-next-line */
        return view('payment-gateway::spa-test')
            ->with('payables', $payables)
            ->with('payments', $payments);
    })->name('payment-gateway-test-spa');
}


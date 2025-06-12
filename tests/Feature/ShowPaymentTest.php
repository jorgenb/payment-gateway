<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Models\Payment;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\get;

it('shows a payment', function (): void {
    $payment = Payment::factory()
        ->hasRefunds(2)
        ->create();

    $this->withoutMiddleware();

    get(route('api.payments.show', $payment))
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) => $json->hasAll([
                'id',
                'payable_id',
                'payable_type',
                'total_charged_amount',
                'total_refunded_amount',
                'total_pending_refunded_amount',
                'refunds',
            ])
                ->has('refunds', 2)
                ->etc()
        );
});

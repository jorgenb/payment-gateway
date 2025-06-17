<?php

declare(strict_types=1);

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Bilberry\PaymentGateway\Enums\PaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Polymorphic relationship to associate the payment with any payable model (e.g., orders, invoices, subscriptions, etc.).
            $table->uuidMorphs('payable');
            $table->string('provider');
            $table->string('type')->default(PaymentType::ONE_TIME->value);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status')->default(PaymentStatus::PENDING->value);
            $table->string('external_id')->unique()->nullable();
            $table->string('external_charge_id')->unique()->nullable();
            $table->string('reference')->nullable();

            $table->string('context_id', 255)->nullable()->comment('Arbitrary context identifier used for resolving provider config. Set by the consuming app.');
            $table->index('context_id');
            $table->json('metadata')->nullable();

            // Indicates the timestamp at which a payment should be manually captured.
            // If null, capture will occur immediately or be handled automatically depending on other conditions.
            $table->timestamp('capture_at')->nullable()->index();

            // Controls whether auto-capture should be enabled for this payment.
            // If true, the provider will attempt to capture the payment immediately upon authorization.
            // If false or null, capture must be triggered manually or as part of a callback flow
            $table->boolean('auto_capture')->default(true);
            $table->timestamps();

            $table->index(['payable_id', 'payable_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

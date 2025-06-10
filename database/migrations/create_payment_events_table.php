<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_id');
            $table->string('event'); // e.g. 'created', 'updated', 'failed'
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};

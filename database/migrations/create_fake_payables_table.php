<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if ( ! app()->environment('production')) {
    return new class () extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('fake_payables', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('fake_payables');
        }
    };
}

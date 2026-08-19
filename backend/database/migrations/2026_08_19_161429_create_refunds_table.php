<?php

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
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('payment_id')
                ->unique()
                ->constrained('payments')
                ->onDelete('restrict');

            $table->foreignUuid('agency_id')
                ->nullable()
                ->constrained('agencies')
                ->onDelete('restrict');

            $table->decimal('percentage', 5, 2)->default(50.00);
            $table->decimal('refunded_amount', 10, 2)->nullable();

            $table->enum('decision_source', [
                'automatic',
                'agency',
                'admin',
            ])->default('automatic');

            $table->enum('status', [
                'pending',
                'processing',
                'processed',
                'failed',
            ])->default('pending');

            $table->text('reason')->nullable();

            $table->timestamp('decided_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

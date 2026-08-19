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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('reservation_id')
                ->unique()
                ->constrained('reservations')
                ->onDelete('restrict');

            $table->decimal('amount', 10, 2);

            $table->decimal('commission_rate', 5, 2);
            $table->decimal('platform_commission', 10, 2);
            $table->decimal('agency_amount', 10, 2);

            $table->string('transaction_id')->nullable()->unique();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();
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

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
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('client_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignUuid('car_id')
                ->constrained('cars')
                ->onDelete('restrict');

            $table->foreignUuid('agency_id')
                ->constrained('agencies')
                ->onDelete('restrict');

            $table->foreignUuid('pickup_point_id')
                ->constrained('agency_points')
                ->onDelete('restrict');

            $table->foreignUuid('return_point_id')
                ->constrained('agency_points')
                ->onDelete('restrict');

            $table->string('reference')->unique();

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->decimal('daily_price_snapshot', 10, 2); // because price can be changed 
            $table->decimal('total_amount', 10, 2);

            $table->enum('status', [
                'pending',
                'confirmed',
                'picked_up',
                'completed',
                'cancelled',
                'disputed',
            ])->default('pending');

            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

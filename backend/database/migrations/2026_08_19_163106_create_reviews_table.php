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
        Schema::create('reviews', function (Blueprint $table) {
           $table->uuid('id')->primary();

            $table->foreignUuid('reservation_id')
                ->unique()
                ->constrained('reservations')
                ->onDelete('restrict');

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->decimal('car_rating', 2, 1);
            $table->decimal('agency_rating', 2, 1);

            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

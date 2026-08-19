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
        Schema::create('agency_points', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('agency_id')
                ->constrained('agencies')
                ->onDelete('restrict');

            $table->foreignUuid('city_id')
                ->constrained('cities')
                ->onDelete('restrict');

            $table->string('name');
            $table->string('address');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('allows_pickup')->default(true);
            $table->boolean('allows_return')->default(true);

            $table->json('opening_hours')->nullable();
            $table->text('instructions')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_points');
    }
};

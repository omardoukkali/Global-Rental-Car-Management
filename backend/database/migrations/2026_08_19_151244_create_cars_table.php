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
        Schema::create('cars', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('agency_id')
                ->constrained('agencies')
                ->onDelete('restrict');

            $table->foreignUuid('city_id')
                ->constrained('cities')
                ->onDelete('restrict');

            $table->string('brand');
            $table->string('model');

            $table->integer('year');

            $table->string('color')->nullable();

            $table->string('plate_number')->unique();

            $table->enum('type', [
                'sedan', 
                'suv', 
                'hatchback', 
                'coupe', 
                'van', 
                'truck'
            ]);

            $table->enum('transmission', [
                'manual',
                'automatic',
            ]);

            $table->integer('seats');

            $table->decimal('daily_price', 10, 2);

            $table->enum('energy_type', [
                'gasoline',
                'diesel',
                'hybrid',
                'electric',
            ]);

            $table->decimal('fuel_consumption', 5, 2)->nullable(); // ?L per 100km

            $table->integer('electric_range')->nullable(); //  autonomy

            $table->enum('status', [
                'available',
                'unavailable',
                'maintenance',
            ])->default('available');

            $table->softDeletes();
            $table->timestamps();
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};

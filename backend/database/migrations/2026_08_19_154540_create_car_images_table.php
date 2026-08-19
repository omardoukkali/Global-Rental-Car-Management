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
        Schema::create('car_images', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('car_id')
                ->constrained('cars')
                ->onDelete('cascade');

            $table->string('url');

            $table->boolean('is_primary')->default(false);

            $table->unsignedInteger('display_order')->default(0); //unsignedInteger = only positive numbers 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_images');
    }
};

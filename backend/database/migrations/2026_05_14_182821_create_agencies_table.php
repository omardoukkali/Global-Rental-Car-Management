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
        Schema::create('agencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('city_id')->constrained('cities')->onDelete('restrict');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();

            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};

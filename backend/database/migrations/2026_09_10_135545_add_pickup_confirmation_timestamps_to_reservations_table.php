<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('client_pickup_confirmed_at')->nullable();
            $table->timestamp('agency_pickup_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'client_pickup_confirmed_at',
                'agency_pickup_confirmed_at',
            ]);
        });
    }
};
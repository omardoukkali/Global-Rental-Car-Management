<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'paid'::character varying, 'released'::character varying, 'failed'::character varying, 'refunded'::character varying, 'partially_refunded'::character varying]::text[]))");
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'paid', 'released', 'failed', 'refunded', 'partially_refunded') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        DB::table('payments')
            ->whereIn('status', ['refunded', 'partially_refunded'])
            ->update(['status' => 'paid']);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'paid'::character varying, 'released'::character varying, 'failed'::character varying]::text[]))");
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'paid', 'released', 'failed') NOT NULL DEFAULT 'pending'");
        }
    }
};

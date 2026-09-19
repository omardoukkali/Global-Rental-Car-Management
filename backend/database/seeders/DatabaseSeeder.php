<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds demo data. Run it on an empty database:
     *   php artisan migrate:fresh --seed
     *
     * The order matters: each seeder uses the data created before it.
     */
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            UserSeeder::class,
            AgencySeeder::class,
            AgencyPointSeeder::class,
            CarSeeder::class,
            CarImageSeeder::class,
            ReservationSeeder::class,
            PaymentSeeder::class,
            RefundSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}

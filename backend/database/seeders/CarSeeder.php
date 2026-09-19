<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * 8 cars per approved agency (one of them in maintenance).
     * The pending agency also gets 2 cars, which must NOT appear in the public catalogue.
     */
    public function run(): void
    {
        $approvedAgencies = Agency::where('status', 'approved')->get();

        foreach ($approvedAgencies as $agency) {
            Car::factory()->count(7)->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
            ]);

            Car::factory()->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
                'status' => 'maintenance',
            ]);
        }

        $pendingAgencies = Agency::where('status', 'pending')->get();

        foreach ($pendingAgencies as $agency) {
            Car::factory()->count(2)->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
            ]);
        }
    }
}

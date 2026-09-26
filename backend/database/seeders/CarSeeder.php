<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * 8 cars per approved agency (one of them in maintenance).
     * Pending agencies cannot manage a fleet until they are approved.
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
    }
}

<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyPoint;
use Illuminate\Database\Seeder;

class AgencyPointSeeder extends Seeder
{
    /**
     * Two pickup/return points for every approved agency, in the agency's city.
     */
    public function run(): void
    {
        $agencies = Agency::where('status', 'approved')->get();

        foreach ($agencies as $agency) {
            AgencyPoint::factory()->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
                'name' => $agency->name . ' - Centre-ville',
            ]);

            AgencyPoint::factory()->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
                'name' => $agency->name . ' - Aéroport',
                'instructions' => 'Rendez-vous au parking P2, en face du hall des arrivées.',
            ]);
        }
    }
}

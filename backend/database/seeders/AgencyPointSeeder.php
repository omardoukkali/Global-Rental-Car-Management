<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyPoint;
use Illuminate\Database\Seeder;

class AgencyPointSeeder extends Seeder
{
    /**
     * Two pickup/return points for every approved agency, in the agency's city:
     * one in the city centre and one at the airport.
     */
    public function run(): void
    {
        $airports = [
            'Tangier' => 'Aéroport Tanger Ibn Battouta',
            'Casablanca' => 'Aéroport Mohammed V',
            'Rabat' => 'Aéroport Rabat-Salé',
            'Marrakech' => 'Aéroport Marrakech-Ménara',
            'Agadir' => 'Aéroport Agadir Al Massira',
        ];

        $agencies = Agency::with('city')->where('status', 'approved')->get();

        foreach ($agencies as $agency) {
            AgencyPoint::factory()->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
                'name' => $agency->name . ' - Centre-ville',
                'address' => $agency->address,
            ]);

            AgencyPoint::factory()->create([
                'agency_id' => $agency->id,
                'city_id' => $agency->city_id,
                'name' => $agency->name . ' - Aéroport',
                'address' => $airports[$agency->city->name] . ', ' . $agency->city->name,
                'instructions' => 'Rendez-vous au parking P2, en face du hall des arrivées.',
            ]);
        }
    }
}

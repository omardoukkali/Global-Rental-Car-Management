<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['name' => 'Casablanca',  'region' => 'Casablanca-Settat'],
            ['name' => 'Rabat',       'region' => 'Rabat-Salé-Kénitra'],
            ['name' => 'Marrakech',   'region' => 'Marrakech-Safi'],
            ['name' => 'Agadir',      'region' => 'Souss-Massa'],
            ['name' => 'Tanger',      'region' => 'Tanger-Tétouan-Al Hoceïma'],
            ['name' => 'Fes',         'region' => 'Fès-Meknès'],
            ['name' => 'Oujda',       'region' => 'Oriental'],
        ];
        foreach ($cities as $city) {
            City::create([
                'id'        => Str::uuid(),
                'name'      => $city['name'],
                'region'    => $city['region'],
                'country'   => 'Morocco',
                'is_active' => true,
            ]);
        }
    }
}

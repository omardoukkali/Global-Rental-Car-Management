<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            [
                'name' => 'Tangier',
                'region' => 'Tanger-Tetouan-Al Hoceima',
                'country' => 'Morocco',
                'is_active' => true,
            ],
            [
                'name' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'country' => 'Morocco',
                'is_active' => true,
            ],
            [
                'name' => 'Rabat',
                'region' => 'Rabat-Sale-Kenitra',
                'country' => 'Morocco',
                'is_active' => true,
            ],
            [
                'name' => 'Marrakech',
                'region' => 'Marrakech-Safi',
                'country' => 'Morocco',
                'is_active' => true,
            ],
            [
                'name' => 'Agadir',
                'region' => 'Souss-Massa',
                'country' => 'Morocco',
                'is_active' => true,
            ],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(
                [
                    'name' => $city['name'],
                    'country' => $city['country'],
                ],
                $city
            );
        }
    }
}
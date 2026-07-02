<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Car;
use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get agencies and cities
        $automaroc     = Agency::where('slug', 'automaroc')->first();
        $marrakechcars = Agency::where('slug', 'marrakech-cars')->first();

        $casablanca = City::where('name', 'Casablanca')->first();
        $marrakech  = City::where('name', 'Marrakech')->first();

        // AutoMaroc cars
        Car::create([
            'id'           => Str::uuid(),
            'agency_id'    => $automaroc->id,
            'city_id'      => $casablanca->id,
            'brand'        => 'Toyota',
            'model'        => 'Corolla',
            'year'         => 2022,
            'color'        => 'White',
            'plate_number' => 'A-12345-B',
            'type'         => 'sedan',
            'transmission' => 'automatic',
            'seats'        => 5,
            'price_per_day'=> 350.00,
            'description'  => 'Comfortable sedan perfect for city driving',
            'status'       => 'available',
            'avg_rating'   => 0,
            'total_reviews'=> 0,
        ]);

        Car::create([
            'id'           => Str::uuid(),
            'agency_id'    => $automaroc->id,
            'city_id'      => $casablanca->id,
            'brand'        => 'Dacia',
            'model'        => 'Duster',
            'year'         => 2023,
            'color'        => 'Grey',
            'plate_number' => 'A-67890-B',
            'type'         => 'suv',
            'transmission' => 'manual',
            'seats'        => 5,
            'price_per_day'=> 450.00,
            'description'  => 'Powerful SUV for road trips',
            'status'       => 'available',
            'avg_rating'   => 0,
            'total_reviews'=> 0,
        ]);

        // MarrakechCars cars
        Car::create([
            'id'           => Str::uuid(),
            'agency_id'    => $marrakechcars->id,
            'city_id'      => $marrakech->id,
            'brand'        => 'Renault',
            'model'        => 'Clio',
            'year'         => 2021,
            'color'        => 'Red',
            'plate_number' => 'M-11111-C',
            'type'         => 'hatchback',
            'transmission' => 'manual',
            'seats'        => 5,
            'price_per_day'=> 250.00,
            'description'  => 'Economic city car',
            'status'       => 'available',
            'avg_rating'   => 0,
            'total_reviews'=> 0,
        ]);

        Car::create([
            'id'           => Str::uuid(),
            'agency_id'    => $marrakechcars->id,
            'city_id'      => $marrakech->id,
            'brand'        => 'Mercedes',
            'model'        => 'GLE',
            'year'         => 2023,
            'color'        => 'Black',
            'plate_number' => 'M-22222-C',
            'type'         => 'suv',
            'transmission' => 'automatic',
            'seats'        => 7,
            'price_per_day'=> 1200.00,
            'description'  => 'Luxury SUV for premium experience',
            'status'       => 'available',
            'avg_rating'   => 0,
            'total_reviews'=> 0,
        ]);
    }
}

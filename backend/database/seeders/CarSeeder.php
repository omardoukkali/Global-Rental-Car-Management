<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Car;
use App\Models\CarImage;
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
        // Get agencies
        $automaroc = Agency::where('slug', 'automaroc-premium')->first();
        $oasis     = Agency::where('slug', 'oasis-drive-marrakech')->first();
        $tanger    = Agency::where('slug', 'tanger-exotics')->first();
        $atlas     = Agency::where('slug', 'atlas-budget')->first();

        // Get cities
        $casablanca = City::where('name', 'Casablanca')->first();
        $marrakech  = City::where('name', 'Marrakech')->first();
        $tangerCity = City::where('name', 'Tanger')->first();


        // ── AutoMaroc Premium Cars (Casablanca) ─────────────────────────────────
        $this->seedCar(
            $automaroc, $casablanca,
            'Mercedes-Benz', 'C-Class', 2023, 'White', 'A-10001-B', 'sedan', 'automatic', 5, 800.00,
            'Executive sedan providing maximum comfort for business trips.',
            'https://images.unsplash.com/photo-1617788138017-80ad40651399?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $automaroc, $casablanca,
            'Range Rover', 'Evoque', 2022, 'Black', 'A-10002-B', 'suv', 'automatic', 5, 1200.00,
            'Luxury SUV with premium interior and advanced safety features.',
            'https://images.unsplash.com/photo-1606152421802-db97b9c7a11b?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $automaroc, $casablanca,
            'Toyota', 'Corolla Hybrid', 2024, 'Silver', 'A-10003-B', 'sedan', 'automatic', 5, 400.00,
            'Fuel-efficient and reliable hybrid for everyday city driving.',
            'https://images.unsplash.com/photo-1629897048514-3dd74142fffc?auto=format&fit=crop&q=80&w=800'
        );
        
        $this->seedCar(
            $automaroc, $casablanca,
            'Peugeot', '208', 2023, 'Blue', 'A-10004-B', 'hatchback', 'manual', 5, 280.00,
            'Compact, stylish, and perfect for navigating city traffic.',
            'https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&q=80&w=800'
        );
        
        $this->seedCar(
            $automaroc, $casablanca,
            'Renault', 'Clio 5', 2022, 'White', 'A-10005-B', 'hatchback', 'manual', 5, 250.00,
            'The most popular rental car in Morocco. Very economical.',
            'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $automaroc, $casablanca,
            'Dacia', 'Logan', 2023, 'Silver', 'A-10006-B', 'sedan', 'manual', 5, 220.00,
            'Spacious budget sedan with a massive trunk for airport luggage.',
            'https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&q=80&w=800'
        );


        // ── Oasis Drive Marrakech Cars (Marrakech) ──────────────────────────────
        $this->seedCar(
            $oasis, $marrakech,
            'Dacia', 'Duster', 2023, 'Grey', 'M-20001-C', 'suv', 'manual', 5, 350.00,
            'Rugged and spacious SUV perfect for exploring the Atlas Mountains.',
            'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $oasis, $marrakech,
            'Jeep', 'Wrangler', 2021, 'Red', 'M-20002-C', 'suv', 'automatic', 4, 900.00,
            'The ultimate off-road adventure vehicle with a removable top.',
            'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $oasis, $marrakech,
            'Volkswagen', 'Golf 8', 2022, 'Blue', 'M-20003-C', 'hatchback', 'automatic', 5, 450.00,
            'Dynamic and sporty hatchback loaded with modern tech.',
            'https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&q=80&w=800'
        );
        
        $this->seedCar(
            $oasis, $marrakech,
            'Opel', 'Corsa', 2022, 'Black', 'M-20004-C', 'hatchback', 'manual', 5, 270.00,
            'German engineering in a compact, easy-to-park form factor.',
            'https://images.unsplash.com/photo-1616422285623-14ff01620e10?auto=format&fit=crop&q=80&w=800'
        );
        
        $this->seedCar(
            $oasis, $marrakech,
            'Dacia', 'Lodgy', 2021, 'White', 'M-20005-C', 'van', 'manual', 7, 400.00,
            '7-seater family vehicle ideal for large groups traveling together.',
            'https://images.unsplash.com/photo-1559416523-140ddc3d238c?auto=format&fit=crop&q=80&w=800'
        );


        // ── Tanger Exotics (Tanger) ─────────────────────────────────────────────
        $this->seedCar(
            $tanger, $tangerCity,
            'Porsche', '911 Carrera', 2023, 'Silver', 'T-30001-D', 'coupe', 'automatic', 2, 2500.00,
            'Iconic sports car delivering an unforgettable driving experience along the coast.',
            'https://images.unsplash.com/photo-1503376760367-11ea8eb22591?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $tanger, $tangerCity,
            'Audi', 'RS7', 2024, 'Black', 'T-30002-D', 'sedan', 'automatic', 4, 2000.00,
            'Aggressive performance wrapped in a sophisticated luxury design.',
            'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $tanger, $tangerCity,
            'Ford', 'Mustang GT', 2022, 'Red', 'T-30003-D', 'coupe', 'automatic', 4, 1100.00,
            'Classic American muscle car with a powerful V8 engine.',
            'https://images.unsplash.com/photo-1584345611124-25500ce04c66?auto=format&fit=crop&q=80&w=800'
        );
        
        // ── Atlas Budget Rentals (Casablanca) - Pending ─────────────────────────────
        $this->seedCar(
            $atlas, $casablanca,
            'Dacia', 'Dokker', 2021, 'White', 'A-40001-B', 'van', 'manual', 5, 200.00,
            'Practical utility vehicle with sliding doors. Great for cargo or family.',
            'https://images.unsplash.com/photo-1563720360172-67b8f3dce741?auto=format&fit=crop&q=80&w=800'
        );

        $this->seedCar(
            $atlas, $casablanca,
            'Dacia', 'Logan', 2022, 'Grey', 'A-40002-B', 'sedan', 'manual', 5, 180.00,
            'The absolute best budget car in Morocco. Ultra reliable.',
            'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800'
        );
        
        $this->seedCar(
            $atlas, $casablanca,
            'Peugeot', '208', 2021, 'Red', 'A-40003-B', 'hatchback', 'manual', 5, 230.00,
            'Affordable and stylish city car.',
            'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=800'
        );
    }

    private function seedCar($agency, $city, $brand, $model, $year, $color, $plate, $type, $transmission, $seats, $price, $desc, $imgUrl)
    {
        $car = Car::create([
            'id'           => Str::uuid(),
            'agency_id'    => $agency->id,
            'city_id'      => $city->id,
            'brand'        => $brand,
            'model'        => $model,
            'year'         => $year,
            'color'        => $color,
            'plate_number' => $plate,
            'type'         => $type,
            'transmission' => $transmission,
            'seats'        => $seats,
            'price_per_day'=> $price,
            'description'  => $desc,
            'status'       => 'available',
            'avg_rating'   => 0,
            'total_reviews'=> 0,
        ]);

        CarImage::create([
            'id' => Str::uuid(),
            'car_id' => $car->id,
            'url' => $imgUrl,
            'is_primary' => true,
        ]);
    }
}

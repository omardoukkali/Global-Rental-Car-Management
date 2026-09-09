<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Car;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::where('name', 'Tangier')
            ->where('country', 'Morocco')
            ->first() ?? City::first();

        $casa = City::where('name', 'Casablanca')->first() ?? $city;

        // Create demo client
        User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'password' => Hash::make('password'),
                'phone' => '+212600000001',
                'role' => 'client',
                'status' => 'active',
            ]
        );

        // Create demo agency owner
        $agencyUser = User::firstOrCreate(
            ['email' => 'agency@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Agency',
                'password' => Hash::make('password'),
                'phone' => '+212600000002',
                'role' => 'agency',
                'status' => 'active',
            ]
        );

        // Create demo agency
        Agency::firstOrCreate(
            ['owner_id' => $agencyUser->id],
            [
                'city_id' => $city->id,
                'name' => 'Demo Rent Cars',
                'slug' => 'demo-rent-cars',
                'address' => 'Tangier, Morocco',
                'phone' => '+212600000003',
                'email' => 'agency@example.com',
                'status' => 'approved',
                'commission_rate' => 15,
                'avg_rating' => 5.0,
                'total_reviews' => 10,
            ]
        );

        // Create Hassan agency (Atlas Cars)
        $hassan = User::firstOrCreate(
            ['email' => 'hassan@agency.ma'],
            [
                'first_name' => 'Hassan',
                'last_name' => 'Atlas',
                'password' => Hash::make('password'),
                'phone' => '+212611223344',
                'role' => 'agency',
                'status' => 'active',
            ]
        );

        $atlasAgency = Agency::firstOrCreate(
            ['owner_id' => $hassan->id],
            [
                'city_id' => $casa->id,
                'name' => 'Atlas Cars',
                'slug' => 'atlas-cars',
                'address' => 'Boulevard d\'Anfa, Casablanca',
                'phone' => '+212522001122',
                'email' => 'hassan@agency.ma',
                'status' => 'approved',
                'commission_rate' => 15,
                'avg_rating' => 5.0,
                'total_reviews' => 34,
            ]
        );

        // Seed demo cars for Atlas Cars
        if ($atlasAgency->cars()->count() === 0) {
            $atlasAgency->cars()->createMany([
                [
                    'city_id' => $casa->id,
                    'brand' => 'Dacia',
                    'model' => 'Logan II',
                    'year' => 2023,
                    'plate_number' => '12345-A-1',
                    'color' => 'Gris',
                    'type' => 'sedan',
                    'transmission' => 'manual',
                    'seats' => 5,
                    'daily_price' => 270,
                    'energy_type' => 'diesel',
                    'status' => 'available',
                ],
                [
                    'city_id' => $casa->id,
                    'brand' => 'Renault',
                    'model' => 'Clio 5',
                    'year' => 2024,
                    'plate_number' => '67890-B-1',
                    'color' => 'Noir',
                    'type' => 'hatchback',
                    'transmission' => 'manual',
                    'seats' => 5,
                    'daily_price' => 300,
                    'energy_type' => 'gasoline',
                    'status' => 'available',
                ],
                [
                    'city_id' => $casa->id,
                    'brand' => 'Peugeot',
                    'model' => '208',
                    'year' => 2023,
                    'plate_number' => '45678-C-1',
                    'color' => 'Blanc',
                    'type' => 'hatchback',
                    'transmission' => 'automatic',
                    'seats' => 5,
                    'daily_price' => 280,
                    'energy_type' => 'diesel',
                    'status' => 'maintenance',
                ],
            ]);
        }
    }
}
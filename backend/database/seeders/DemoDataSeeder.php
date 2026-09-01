<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::where('name', 'Tangier')
            ->where('country', 'Morocco')
            ->firstOrFail();

        // Create demo client
        User::firstOrCreate(
            [
                'email' => 'client@example.com',
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'password' => 'password',
                'phone' => '+212600000001',
                'role' => 'client',
                'status' => 'active',
            ]
        );

        // Create demo agency owner
        $agencyUser = User::firstOrCreate(
            [
                'email' => 'agency@example.com',
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Agency',
                'password' => 'password',
                'phone' => '+212600000002',
                'role' => 'agency',
                'status' => 'active',
            ]
        );

        // Create demo agency
        Agency::firstOrCreate(
            [
                'owner_id' => $agencyUser->id,
            ],
            [
                'city_id' => $city->id,
                'name' => 'Demo Rent Cars',
                'slug' => 'demo-rent-cars',
                'address' => 'Tangier, Morocco',
                'phone' => '+212600000003',
                'email' => 'agency@example.com',
                'status' => 'pending',
                'commission_rate' => 15,
                'avg_rating' => 0,
                'total_reviews' => 0,
            ]
        );
    }
}
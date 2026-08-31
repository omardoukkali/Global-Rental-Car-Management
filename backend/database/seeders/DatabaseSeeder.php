<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\City;
use App\Models\Agency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $city = City::firstOrCreate(
            ['name' => 'Casablanca'],
            ['region' => 'Casablanca-Settat', 'country' => 'Morocco']
        );

        $client = User::firstOrCreate(
            ['email' => 'client@test.ma'],
            [
                'first_name' => 'Sara',
                'last_name' => 'Idrissi',
                'password' => Hash::make('password'),
                'role' => 'client',
                'status' => 'active',
            ]
        );

        $agencyOwner = User::firstOrCreate(
            ['email' => 'hassan@agency.ma'],
            [
                'first_name' => 'Hassan',
                'last_name' => 'Alami',
                'password' => Hash::make('password'),
                'role' => 'agency',
                'status' => 'active',
            ]
        );

        Agency::firstOrCreate(
            ['owner_id' => $agencyOwner->id],
            [
                'city_id' => $city->id,
                'name' => 'Atlas Cars',
                'slug' => 'atlas-cars',
                'address' => "Boulevard d'Anfa, Casablanca",
                'phone' => '+212 522 123 456',
                'email' => 'contact@atlascars.ma',
                'status' => 'approved',
                'commission_rate' => 15.00,
                'avg_rating' => 5.0,
                'total_reviews' => 24,
            ]
        );

        $this->call([
            AdminUserSeeder::class,
            CitySeeder::class,
        ]);
    }
}

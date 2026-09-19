<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * 12 agencies: 10 approved, 1 pending, 1 rejected.
     * Each agency owner is a user with the "agency" role (password: password).
     */
    public function run(): void
    {
        $tangier = City::where('name', 'Tangier')->first();
        $casablanca = City::where('name', 'Casablanca')->first();

        // Fixed demo agencies, so the demo logins always work
        $this->createAgency(
            'agency@example.com',
            'Demo',
            'Agency',
            'Demo Rent Cars',
            'demo-rent-cars',
            $tangier,
            'approved'
        );

        $this->createAgency(
            'hassan@agency.ma',
            'Hassan',
            'Atlas',
            'Atlas Cars',
            'atlas-cars',
            $casablanca,
            'approved'
        );

        // Random agencies in random cities
        $cities = City::all();

        for ($i = 0; $i < 8; $i++) {
            $this->createRandomAgency($cities->random(), 'approved');
        }

        $this->createRandomAgency($cities->random(), 'pending');
        $this->createRandomAgency($cities->random(), 'rejected');
    }

    private function createAgency(
        string $email,
        string $firstName,
        string $lastName,
        string $agencyName,
        string $slug,
        City $city,
        string $status
    ): void {
        $owner = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => 'password',
                'phone' => '+2126' . fake()->numerify('########'),
                'role' => 'agency',
                'status' => 'active',
            ]
        );

        Agency::firstOrCreate(
            ['owner_id' => $owner->id],
            [
                'city_id' => $city->id,
                'name' => $agencyName,
                'slug' => $slug,
                'address' => fake()->streetAddress() . ', ' . $city->name,
                'phone' => '+2125' . fake()->numerify('########'),
                'email' => $email,
                'status' => $status,
                'commission_rate' => 15,
            ]
        );
    }

    private function createRandomAgency(City $city, string $status): void
    {
        $owner = User::factory()->create([
            'role' => 'agency',
            'status' => 'active',
        ]);

        Agency::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $city->id,
            'status' => $status,
            // Different commissions, to show that payments use the agency rate
            'commission_rate' => fake()->randomElement([10, 12.5, 15, 18]),
        ]);
    }
}

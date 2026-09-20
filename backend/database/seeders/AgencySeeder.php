<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgencySeeder extends Seeder
{
    /**
     * 12 agencies: 10 approved, 1 pending, 1 rejected.
     * Each agency owner is a user with the "agency" role (password: password).
     */
    public function run(): void
    {
        // Fixed demo agencies, so the demo logins always work
        $this->createAgency('agency@example.com', 'Karim', 'Tazi', 'Demo Rent Cars', 'Tangier', 'approved');
        $this->createAgency('hassan@agency.ma', 'Hassan', 'Alaoui', 'Atlas Cars', 'Casablanca', 'approved');

        $this->createAgency('contact@marrakech-car-rent.ma', 'Youssef', 'Bennani', 'Marrakech Car Rent', 'Marrakech', 'approved');
        $this->createAgency('contact@menara-location.ma', 'Salma', 'Berrada', 'Ménara Location', 'Marrakech', 'approved');
        $this->createAgency('contact@agadir-location.ma', 'Omar', 'El Idrissi', 'Agadir Location Auto', 'Agadir', 'approved');
        $this->createAgency('contact@souss-cars.ma', 'Imane', 'Chraibi', 'Souss Cars', 'Agadir', 'approved');
        $this->createAgency('contact@rabat-drive.ma', 'Mehdi', 'Lahlou', 'Rabat Drive', 'Rabat', 'approved');
        $this->createAgency('contact@casa-wheels.ma', 'Zineb', 'El Fassi', 'Casa Wheels', 'Casablanca', 'approved');
        $this->createAgency('contact@tanger-med-cars.ma', 'Reda', 'Benjelloun', 'Tanger Med Cars', 'Tangier', 'approved');
        $this->createAgency('contact@royal-cars.ma', 'Houda', 'Kettani', 'Royal Cars Maroc', 'Rabat', 'approved');
        $this->createAgency('contact@riad-location.ma', 'Hamza', 'Benali', 'Riad Location', 'Marrakech', 'pending');
        $this->createAgency('contact@sahara-auto.ma', 'Meryem', 'El Amrani', 'Sahara Auto Location', 'Agadir', 'rejected');
    }

    private function createAgency(
        string $email,
        string $firstName,
        string $lastName,
        string $agencyName,
        string $cityName,
        string $status
    ): void {
        $city = City::where('name', $cityName)->first();

        $streets = ['Avenue Mohammed V', 'Boulevard Hassan II', 'Rue Ibn Battouta', 'Avenue des FAR', 'Boulevard Zerktouni'];

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
                'slug' => Str::slug($agencyName),
                'address' => fake()->numberBetween(1, 200) . ', ' . fake()->randomElement($streets) . ', ' . $city->name,
                'phone' => '+2125' . fake()->numerify('########'),
                'email' => $email,
                'status' => $status,
                // Different commissions, to show that payments use the agency rate
                'commission_rate' => fake()->randomElement([10, 12.5, 15, 18]),
            ]
        );
    }
}

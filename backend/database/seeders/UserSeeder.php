<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Admin and client accounts.
     * Agency owners are created in AgencySeeder, together with their agency.
     * Every seeded account uses the password: password
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => 'password',
            ]
        );

        // role and status are not mass assignable (F-03)
        $admin->role = 'admin';
        $admin->status = 'active';
        $admin->save();

        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'password' => 'password',
                'phone' => '+212600000001',
            ]
        );

        $client->role = 'client';
        $client->status = 'active';
        $client->save();

        // 29 more clients (30 in total)
        $firstNames = ['Youssef', 'Amina', 'Mohamed', 'Salma', 'Omar', 'Khadija', 'Hamza', 'Imane', 'Ayoub', 'Sara', 'Mehdi', 'Hiba', 'Yassine', 'Meryem', 'Othmane', 'Zineb', 'Karim', 'Houda', 'Reda', 'Ghita'];
        $lastNames = ['El Amrani', 'Benali', 'Alaoui', 'Bennani', 'Tazi', 'El Idrissi', 'Chraibi', 'Berrada', 'El Fassi', 'Lahlou', 'Benjelloun', 'Kettani'];

        for ($i = 1; $i <= 29; $i++) {
            $firstName = fake()->randomElement($firstNames);
            $lastName = fake()->randomElement($lastNames);

            User::factory()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName) . $i . '@example.ma',
                'phone' => '+2126' . fake()->numerify('########'),
                'role' => 'client',
                'status' => 'active',
            ]);
        }
    }
}

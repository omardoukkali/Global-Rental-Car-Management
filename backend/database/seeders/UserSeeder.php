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
        // firstOrNew, because role and status are not mass assignable (F-03)
        // and the columns cannot be null
        $admin = User::firstOrNew(['email' => 'admin@example.com']);
        $admin->first_name = 'Admin';
        $admin->last_name = 'User';
        $admin->password = 'password';
        $admin->role = 'admin';
        $admin->status = 'active';
        $admin->email_verified_at = now();
        $admin->save();

        $client = User::firstOrNew(['email' => 'client@example.com']);
        $client->first_name = 'Demo';
        $client->last_name = 'Client';
        $client->password = 'password';
        $client->phone = '+212600000001';
        $client->role = 'client';
        $client->status = 'active';
        $client->email_verified_at = now();
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
                'email_verified_at' => now(),
            ]);
        }
    }
}

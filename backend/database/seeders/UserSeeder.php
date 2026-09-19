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
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'password' => 'password',
                'phone' => '+212600000001',
                'role' => 'client',
                'status' => 'active',
            ]
        );

        // 29 more clients (30 in total)
        User::factory()->count(29)->create([
            'role' => 'client',
            'status' => 'active',
        ]);
    }
}

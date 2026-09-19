<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@example.com',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            [
                'email' => 'admin@test.ma',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'ELFAQIR',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}
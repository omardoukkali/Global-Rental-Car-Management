<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'id' => Str::uuid(),
            'first_name' => 'Ahmad',
            'last_name' => 'Zaidi',
            'email' => 'admin@email.com',
            'password' => '123456',
            'phone' => '0656565656',
            'role' => 'admin',
            'status' => 'active'
        ]);

        // ── Clients ──────────────────────────────────
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'client@test.ma',
            'password'   => '123456',
            'phone'      => '0611111111',
            'role'       => 'client',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Sara',
            'last_name'  => 'Smith',
            'email'      => 'sara@test.ma',
            'password'   => '123456',
            'phone'      => '0622222222',
            'role'       => 'client',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Karim',
            'last_name'  => 'Bennani',
            'email'      => 'karim@test.ma',
            'password'   => '123456',
            'phone'      => '0633000111',
            'role'       => 'client',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Fatima',
            'last_name'  => 'Zidane',
            'email'      => 'fatima@test.ma',
            'password'   => '123456',
            'phone'      => '0644000222',
            'role'       => 'client',
            'status'     => 'active',
        ]);

        // ── Agency Owners ────────────────────────────
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Hassan',
            'last_name'  => 'Alaoui',
            'email'      => 'hassan@agency.ma',
            'password'   => '123456',
            'phone'      => '0633333333',
            'role'       => 'agency_owner',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Youssef',
            'last_name'  => 'Benali',
            'email'      => 'youssef@agency.ma',
            'password'   => '123456',
            'phone'      => '0644444444',
            'role'       => 'agency_owner',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Nadia',
            'last_name'  => 'El Fassi',
            'email'      => 'nadia@agency.ma',
            'password'   => '123456',
            'phone'      => '0655500333',
            'role'       => 'agency_owner',
            'status'     => 'active',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Salim',
            'last_name'  => 'Akali',
            'email'      => 'pending@agency.ma',
            'password'   => '123456',
            'phone'      => '0645555444',
            'role'       => 'agency_owner',
            'status'     => 'pending',
        ]);
    }
}

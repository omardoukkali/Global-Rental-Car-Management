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
        //admin
        User::create([
            'id' => Str::uuid(),
            'first_name' => 'ahmad',
            'last_name' => 'zaidi',
            'email' => 'admin@email.com',
            'password' => '123456',
            'phone' => '0656565656',
            'role' => 'admin',
            'status' => 'active'
        ]);
        // Client 1
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

        // Client 2
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

        // Agency owner 1
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

        // Agency owner 2
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
        // Agency owner 3
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'salim',
            'last_name'  => 'akali',
            'email'      => 'pending@agency.ma',
            'password'   => '123456',
            'phone'      => '0645555444',
            'role'       => 'agency_owner',
            'status'     => 'pending',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Agency;
use App\Models\City;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hassan = User::where('email','hassan@agency.ma')->first();
        $youssef = User::where('email', 'youssef@agency.ma')->first();
        $salim = User::where('email', 'pending@agency.ma')->first();

        $casablanca = City::where('name', 'Casablanca')->first();
        $marrakech  = City::where('name', 'Marrakech')->first();

        // Agency 1
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $hassan->id,
            'city_id'     => $casablanca->id,
            'name'        => 'AutoMaroc',
            'slug'        => 'automaroc',
            'description' => 'Best car rental in Casablanca',
            'address'     => '12 Rue Hassan II, Casablanca',
            'phone'       => '0522111111',
            'email'       => 'contact@automaroc.ma',
            'status'      => 'approved',
            'avg_rating'  => 0,
            'total_reviews' => 0,
        ]);

        // Agency 2
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $youssef->id,
            'city_id'     => $marrakech->id,
            'name'        => 'MarrakechCars',
            'slug'        => 'marrakech-cars',
            'description' => 'Premium cars in Marrakech',
            'address'     => '5 Avenue Mohammed V, Marrakech',
            'phone'       => '0524222222',
            'email'       => 'contact@marrakechcars.ma',
            'status'      => 'approved',
            'avg_rating'  => 0,
            'total_reviews' => 0,
        ]);
        // Agency 3
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $salim->id,
            'city_id'     => $marrakech->id,
            'name'        => 'pending cars',
            'slug'        => 'pending-cars',
            'description' => 'Premium cars in Marrakech',
            'address'     => '5 Avenue Mohammed V, Marrakech',
            'phone'       => '0524222222',
            'email'       => 'contact@pending.ma',
            'status'      => 'pending',
            'avg_rating'  => 0,
            'total_reviews' => 0,
        ]);
    }
}

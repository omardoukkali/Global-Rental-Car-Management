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
        $hassan  = User::where('email','hassan@agency.ma')->first();
        $youssef = User::where('email', 'youssef@agency.ma')->first();
        $nadia   = User::where('email', 'nadia@agency.ma')->first();
        $salim   = User::where('email', 'pending@agency.ma')->first();

        $casablanca = City::where('name', 'Casablanca')->first();
        $marrakech  = City::where('name', 'Marrakech')->first();
        $tanger     = City::where('name', 'Tanger')->first();

        // Agency 1
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $hassan->id,
            'city_id'     => $casablanca->id,
            'name'        => 'AutoMaroc Premium',
            'slug'        => 'automaroc-premium',
            'description' => 'The leading provider of premium and economy rental cars in Casablanca. We offer well-maintained vehicles for all your travel needs.',
            'address'     => '12 Rue Hassan II, Casablanca',
            'phone'       => '0522111111',
            'email'       => 'contact@automaroc.ma',
            'status'      => 'approved',
            'avg_rating'  => 4.8,
            'total_reviews' => 124,
        ]);

        // Agency 2
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $youssef->id,
            'city_id'     => $marrakech->id,
            'name'        => 'Oasis Drive Marrakech',
            'slug'        => 'oasis-drive-marrakech',
            'description' => 'Explore the Red City in style. We specialize in SUVs and luxury sedans perfect for both city tours and mountain excursions.',
            'address'     => '5 Avenue Mohammed V, Gueliz, Marrakech',
            'phone'       => '0524222222',
            'email'       => 'booking@oasisdrive.ma',
            'status'      => 'approved',
            'avg_rating'  => 4.5,
            'total_reviews' => 89,
        ]);

        // Agency 3
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $nadia->id,
            'city_id'     => $tanger->id,
            'name'        => 'Tanger Exotics',
            'slug'        => 'tanger-exotics',
            'description' => 'Luxury and sport vehicles available right from the port or airport. Unforgettable experiences start with the right car.',
            'address'     => 'Boulevard Pasteur, Tanger',
            'phone'       => '0539333333',
            'email'       => 'info@tangerexotics.ma',
            'status'      => 'approved',
            'avg_rating'  => 4.9,
            'total_reviews' => 42,
        ]);

        // Agency 4 (Pending)
        Agency::create([
            'id'          => Str::uuid(),
            'owner_id'    => $salim->id,
            'city_id'     => $casablanca->id,
            'name'        => 'Atlas Budget Rentals',
            'slug'        => 'atlas-budget',
            'description' => 'Affordable and reliable cars for your daily commute or long trips across Morocco.',
            'address'     => 'Route El Jadida, Casablanca',
            'phone'       => '0522444555',
            'email'       => 'hello@atlasbudget.ma',
            'status'      => 'pending',
            'avg_rating'  => 0,
            'total_reviews' => 0,
        ]);
    }
}

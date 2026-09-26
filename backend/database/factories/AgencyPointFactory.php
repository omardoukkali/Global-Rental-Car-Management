<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyPoint;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyPointFactory extends Factory
{
    protected $model = AgencyPoint::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'city_id' => City::factory(),
            'name' => 'Agence ' . fake()->streetName(),
            'address' => fake()->streetAddress(),
            // Somewhere in Morocco
            'latitude' => fake()->randomFloat(7, 30, 35.7),
            'longitude' => fake()->randomFloat(7, -9.7, -2),
            'allows_pickup' => true,
            'allows_return' => true,
            'opening_hours' => [
                'monday_friday' => '08:00-19:00',
                'saturday' => '09:00-13:00',
                'sunday' => 'closed',
            ],
            'instructions' => null,
            'is_active' => true,
        ];
    }
}

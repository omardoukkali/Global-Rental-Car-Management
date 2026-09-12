<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'owner_id'        => User::factory(),
            'city_id'         => City::factory(),
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . Str::random(6),
            'logo_url'        => null,
            'address'         => $this->faker->streetAddress(),
            'phone'           => '+2126' . $this->faker->numerify('########'),
            'email'           => $this->faker->unique()->companyEmail(),
            'status'          => 'pending',
            'avg_rating'      => null,
            'total_reviews'   => 0,
            'commission_rate' => 15.00,
        ];
    }

    /**
     * Raccourci : agence approuvée.
     */
    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    /**
     * Raccourci : agence rejetée.
     */
    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}

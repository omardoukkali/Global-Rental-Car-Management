<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarImageFactory extends Factory
{
    protected $model = CarImage::class;

    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            // Placeholder photo service (a different picture for each seed)
            'url' => 'https://picsum.photos/seed/' . fake()->uuid() . '/800/600',
            'is_primary' => false,
            'display_order' => 0,
        ];
    }
}

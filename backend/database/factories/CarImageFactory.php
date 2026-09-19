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
            'url' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/7/7b/Dacia_Logan_III.jpg/960px-Dacia_Logan_III.jpg',
            'is_primary' => false,
            'display_order' => 0,
        ];
    }
}

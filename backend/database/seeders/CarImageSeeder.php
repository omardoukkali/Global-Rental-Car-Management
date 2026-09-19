<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Seeder;

class CarImageSeeder extends Seeder
{
    /**
     * 3 images per car. The first one is the primary image.
     */
    public function run(): void
    {
        $cars = Car::all();

        foreach ($cars as $car) {
            for ($order = 0; $order < 3; $order++) {
                CarImage::factory()->create([
                    'car_id' => $car->id,
                    'is_primary' => $order === 0,
                    'display_order' => $order,
                ]);
            }
        }
    }
}

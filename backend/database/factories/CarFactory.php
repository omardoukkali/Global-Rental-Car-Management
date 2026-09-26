<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Car;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        // [brand, model, type, seats, min price, max price (MAD / day), possible energies]
        $models = [
            ['Dacia', 'Logan', 'sedan', 5, 250, 300, ['diesel', 'gasoline']],
            ['Dacia', 'Sandero', 'hatchback', 5, 250, 300, ['diesel', 'gasoline']],
            ['Dacia', 'Duster', 'suv', 5, 350, 450, ['diesel']],
            ['Renault', 'Clio', 'hatchback', 5, 300, 350, ['diesel', 'gasoline']],
            ['Peugeot', '208', 'hatchback', 5, 300, 380, ['diesel', 'gasoline', 'electric']],
            ['Peugeot', '3008', 'suv', 5, 500, 650, ['diesel', 'hybrid']],
            ['Volkswagen', 'Golf', 'hatchback', 5, 400, 500, ['diesel', 'gasoline']],
            ['Toyota', 'Corolla', 'sedan', 5, 400, 500, ['hybrid']],
            ['Hyundai', 'Tucson', 'suv', 5, 550, 700, ['diesel', 'hybrid']],
            ['Kia', 'Picanto', 'hatchback', 4, 220, 270, ['gasoline']],
            ['Mercedes', 'Classe C', 'sedan', 5, 900, 1200, ['diesel', 'hybrid']],
            ['BMW', 'Série 4', 'coupe', 4, 1000, 1400, ['gasoline']],
            ['Ford', 'Transit', 'van', 9, 600, 800, ['diesel']],
        ];

        $model = fake()->randomElement($models);
        $energyType = fake()->randomElement($model[6]);

        // Energy rules (see ValidatesEnergyConsistency):
        // gasoline/diesel -> fuel only, electric -> range only, hybrid -> both
        $fuelConsumption = null;
        $electricRange = null;

        if ($energyType !== 'electric') {
            $fuelConsumption = fake()->randomFloat(1, 4, 9);
        }

        if ($energyType === 'electric') {
            $electricRange = fake()->numberBetween(300, 550);
        }

        if ($energyType === 'hybrid') {
            $electricRange = fake()->numberBetween(40, 80);
        }

        return [
            'agency_id' => Agency::factory(),
            'city_id' => City::factory(),
            'brand' => $model[0],
            'model' => $model[1],
            'type' => $model[2],
            'seats' => $model[3],
            'year' => fake()->numberBetween(2018, 2025),
            'color' => fake()->randomElement(['Blanc', 'Noir', 'Gris', 'Bleu', 'Rouge']),
            'plate_number' => fake()->unique()->numerify('#####-A-##'),
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            // Rounded to 10 MAD
            'daily_price' => round(fake()->numberBetween($model[4], $model[5]), -1),
            'energy_type' => $energyType,
            'fuel_consumption' => $fuelConsumption,
            'electric_range' => $electricRange,
            'status' => 'available',
        ];
    }
}

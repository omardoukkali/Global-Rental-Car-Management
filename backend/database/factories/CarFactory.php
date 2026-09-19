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
        // [brand, model, type, seats]
        $models = [
            ['Dacia', 'Logan', 'sedan', 5],
            ['Dacia', 'Sandero', 'hatchback', 5],
            ['Dacia', 'Duster', 'suv', 5],
            ['Renault', 'Clio', 'hatchback', 5],
            ['Peugeot', '208', 'hatchback', 5],
            ['Peugeot', '3008', 'suv', 5],
            ['Volkswagen', 'Golf', 'hatchback', 5],
            ['Toyota', 'Corolla', 'sedan', 5],
            ['Hyundai', 'Tucson', 'suv', 5],
            ['Kia', 'Picanto', 'hatchback', 4],
            ['Mercedes', 'Classe C', 'sedan', 5],
            ['BMW', 'Série 4', 'coupe', 4],
            ['Ford', 'Transit', 'van', 9],
        ];

        $model = fake()->randomElement($models);
        $energyType = fake()->randomElement(['gasoline', 'diesel', 'diesel', 'hybrid', 'electric']);

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
            'daily_price' => fake()->numberBetween(25, 120) * 10,
            'energy_type' => $energyType,
            'fuel_consumption' => $fuelConsumption,
            'electric_range' => $electricRange,
            'status' => 'available',
        ];
    }
}

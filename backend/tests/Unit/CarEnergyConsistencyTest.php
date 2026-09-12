<?php

namespace Tests\Unit;

use App\Http\Requests\Car\StoreCarRequest;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Exclusivité entre fuel_consumption (consommation_l_100km) et
 * electric_range (autonomie_electrique_km) selon le type d'énergie.
 */
class CarEnergyConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lance la validation complète de StoreCarRequest, y compris
     * les règles inter-champs déclarées dans after().
     */
    private function passesValidation(array $overrides = []): bool
    {
        $request = new StoreCarRequest();

        $validator = Validator::make(
            $this->carData($overrides),
            $request->rules()
        );

        foreach ($request->after() as $afterCallback) {
            $validator->after($afterCallback);
        }

        return ! $validator->fails();
    }

    private function carData(array $overrides = []): array
    {
        $city = City::factory()->create();

        return array_merge([
            'city_id' => $city->id,
            'brand' => 'Dacia',
            'model' => 'Sandero',
            'year' => 2024,
            'color' => 'White',
            'plate_number' => '12345-A-6',
            'type' => 'hatchback',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'gasoline',
            'fuel_consumption' => 6.2,
            'electric_range' => null,
        ], $overrides);
    }

    // --- Thermique (gasoline / diesel) : fuel requis, range interdit ---

    public function test_gasoline_with_fuel_and_no_range_passes(): void
    {
        $this->assertTrue($this->passesValidation([
            'energy_type' => 'gasoline',
            'fuel_consumption' => 6.2,
            'electric_range' => null,
        ]));
    }

    public function test_gasoline_with_electric_range_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'gasoline',
            'fuel_consumption' => 6.2,
            'electric_range' => 400,
        ]));
    }

    public function test_diesel_with_electric_range_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.1,
            'electric_range' => 300,
        ]));
    }

    public function test_gasoline_without_fuel_consumption_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'gasoline',
            'fuel_consumption' => null,
            'electric_range' => null,
        ]));
    }

    // --- Électrique : range requis, fuel interdit ---

    public function test_electric_with_range_and_no_fuel_passes(): void
    {
        $this->assertTrue($this->passesValidation([
            'energy_type' => 'electric',
            'fuel_consumption' => null,
            'electric_range' => 450,
        ]));
    }

    public function test_electric_with_fuel_consumption_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'electric',
            'fuel_consumption' => 6.2,
            'electric_range' => 450,
        ]));
    }

    public function test_electric_without_range_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'electric',
            'fuel_consumption' => null,
            'electric_range' => null,
        ]));
    }

    // --- Hybride : les deux requis ---

    public function test_hybrid_with_both_passes(): void
    {
        $this->assertTrue($this->passesValidation([
            'energy_type' => 'hybrid',
            'fuel_consumption' => 4.5,
            'electric_range' => 60,
        ]));
    }

    public function test_hybrid_without_range_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'hybrid',
            'fuel_consumption' => 4.5,
            'electric_range' => null,
        ]));
    }

    public function test_hybrid_without_fuel_consumption_fails(): void
    {
        $this->assertFalse($this->passesValidation([
            'energy_type' => 'hybrid',
            'fuel_consumption' => null,
            'electric_range' => 60,
        ]));
    }
}

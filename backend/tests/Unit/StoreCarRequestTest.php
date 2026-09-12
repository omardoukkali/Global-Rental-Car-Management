<?php

namespace Tests\Unit;

use App\Http\Requests\Car\StoreCarRequest;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCarRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): bool
    {
        $request = new StoreCarRequest();

        $validator = Validator::make(
            $data,
            $request->rules()
        );

        return !$validator->fails();
    }

    private function validCarData(): array
    {
        $city = City::factory()->create();

        return [
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
        ];
    }

    public function test_valid_car_data_passes_validation(): void
    {
        $this->assertTrue(
            $this->validate($this->validCarData())
        );
    }

    public function test_brand_is_required(): void
    {
        $data = $this->validCarData();

        unset($data['brand']);

        $this->assertFalse(
            $this->validate($data)
        );
    }

    public function test_invalid_car_type_fails_validation(): void
    {
        $data = $this->validCarData();

        $data['type'] = 'invalid-type';

        $this->assertFalse(
            $this->validate($data)
        );
    }

    public function test_invalid_transmission_fails_validation(): void
    {
        $data = $this->validCarData();

        $data['transmission'] = 'semi-auto';

        $this->assertFalse(
            $this->validate($data)
        );
    }

    public function test_invalid_energy_type_fails_validation(): void
    {
        $data = $this->validCarData();

        $data['energy_type'] = 'water';

        $this->assertFalse(
            $this->validate($data)
        );
    }

    public function test_invalid_year_fails_validation(): void
    {
        $data = $this->validCarData();

        $data['year'] = 1800;

        $this->assertFalse(
            $this->validate($data)
        );
    }

    public function test_negative_daily_price_fails_validation(): void
    {
        $data = $this->validCarData();

        $data['daily_price'] = -100;

        $this->assertFalse(
            $this->validate($data)
        );
    }
}
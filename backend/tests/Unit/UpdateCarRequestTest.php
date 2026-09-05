<?php

namespace Tests\Unit;

use App\Http\Requests\Car\UpdateCarRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCarRequestTest extends TestCase
{
    private function validate(array $data): bool
    {
        $request = new UpdateCarRequest();

        $validator = Validator::make(
            $data,
            $request->rules()
        );

        return !$validator->fails();
    }

    public function test_valid_partial_update_passes_validation(): void
    {
        $this->assertTrue($this->validate([
            'brand' => 'Dacia',
            'model' => 'Sandero',
            'daily_price' => 300,
        ]));
    }

    public function test_update_allows_partial_data(): void
    {
        $this->assertTrue($this->validate([
            'daily_price' => 350,
        ]));
    }

    public function test_invalid_car_type_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'type' => 'invalid-type',
        ]));
    }

    public function test_invalid_transmission_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'transmission' => 'semi-auto',
        ]));
    }

    public function test_invalid_energy_type_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'energy_type' => 'water',
        ]));
    }

    public function test_invalid_year_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'year' => 1800,
        ]));
    }

    public function test_negative_daily_price_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'daily_price' => -100,
        ]));
    }

    public function test_invalid_seats_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'seats' => 0,
        ]));
    }

    public function test_invalid_fuel_consumption_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'fuel_consumption' => -5,
        ]));
    }

    public function test_invalid_electric_range_fails_validation(): void
    {
        $this->assertFalse($this->validate([
            'electric_range' => -100,
        ]));
    }

    public function test_color_can_be_null(): void
    {
        $this->assertTrue($this->validate([
            'color' => null,
        ]));
    }

    public function test_fuel_consumption_can_be_null(): void
    {
        $this->assertTrue($this->validate([
            'fuel_consumption' => null,
        ]));
    }

    public function test_electric_range_can_be_null(): void
    {
        $this->assertTrue($this->validate([
            'electric_range' => null,
        ]));
    }
}
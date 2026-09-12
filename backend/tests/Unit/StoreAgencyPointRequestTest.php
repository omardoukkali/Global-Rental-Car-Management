<?php

namespace Tests\Unit;

use App\Http\Requests\Agency\StoreAgencyPointRequest;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreAgencyPointRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a validator using the FormRequest rules
     * and also apply the custom after() validation.
     */
    private function validateRequest(array $data)
    {
        $request = new StoreAgencyPointRequest();

        // Put the test data inside the FormRequest.
        $request->merge($data);

        // Build the validator using the FormRequest rules.
        $validator = Validator::make(
            $data,
            $request->rules()
        );

        // Apply the custom validation callbacks from after().
        foreach ($request->after() as $callback) {
            $validator->after($callback);
        }

        return $validator;
    }

    public function test_valid_agency_point_location_passes_validation(): void
    {
        $city = City::create([
            'name' => 'Tangier',
            'region' => 'Tanger-Tetouan-Al Hoceima',
            'country' => 'Morocco',
            'is_active' => true,
        ]);

        $validator = $this->validateRequest([
            'city_id' => $city->id,
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 35.7269,
            'longitude' => -5.9169,
            'allows_pickup' => true,
            'allows_return' => false,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_city_id_format_fails_validation(): void
    {
        $validator = $this->validateRequest([
            'city_id' => '123',
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 35.7269,
            'longitude' => -5.9169,
            'allows_pickup' => true,
            'allows_return' => false,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'city_id',
            $validator->errors()->toArray()
        );
    }

    public function test_non_existing_city_fails_validation(): void
    {
        $validator = $this->validateRequest([
            'city_id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 35.7269,
            'longitude' => -5.9169,
            'allows_pickup' => true,
            'allows_return' => false,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'city_id',
            $validator->errors()->toArray()
        );
    }

    public function test_invalid_latitude_fails_validation(): void
    {
        $city = City::create([
            'name' => 'Tangier',
            'region' => 'Tanger-Tetouan-Al Hoceima',
            'country' => 'Morocco',
            'is_active' => true,
        ]);

        $validator = $this->validateRequest([
            'city_id' => $city->id,
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 100,
            'longitude' => -5.9169,
            'allows_pickup' => true,
            'allows_return' => false,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'latitude',
            $validator->errors()->toArray()
        );
    }

    public function test_invalid_longitude_fails_validation(): void
    {
        $city = City::create([
            'name' => 'Tangier',
            'region' => 'Tanger-Tetouan-Al Hoceima',
            'country' => 'Morocco',
            'is_active' => true,
        ]);

        $validator = $this->validateRequest([
            'city_id' => $city->id,
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 35.7269,
            'longitude' => 200,
            'allows_pickup' => true,
            'allows_return' => false,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'longitude',
            $validator->errors()->toArray()
        );
    }

    public function test_both_pickup_and_return_disabled_fails_validation(): void
    {
        $city = City::create([
            'name' => 'Tangier',
            'region' => 'Tanger-Tetouan-Al Hoceima',
            'country' => 'Morocco',
            'is_active' => true,
        ]);

        $validator = $this->validateRequest([
            'city_id' => $city->id,
            'name' => 'Tangier Airport',
            'address' => 'Ibn Battouta Airport',
            'latitude' => 35.7269,
            'longitude' => -5.9169,
            'allows_pickup' => false,
            'allows_return' => false,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'allows_pickup',
            $validator->errors()->toArray()
        );
    }
}
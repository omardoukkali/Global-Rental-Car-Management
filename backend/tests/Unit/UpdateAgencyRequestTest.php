<?php

namespace Tests\Unit;

use App\Http\Requests\Agency\UpdateAgencyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAgencyRequestTest extends TestCase
{
    private function validate(array $data)
    {
        $request = new UpdateAgencyRequest();

        return Validator::make(
            $data,
            $request->rules()
        );
    }

    public function test_valid_agency_data_passes_validation(): void
    {
        $validator = $this->validate([
            'name' => 'ABC Rent Cars',
            'email' => 'agency@example.com',
            'phone' => '+212600000000',
            'address' => 'Tangier, Morocco',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_email_fails_validation(): void
    {
        $validator = $this->validate([
            'email' => 'invalid-email',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'email',
            $validator->errors()->toArray()
        );
    }

    public function test_name_longer_than_255_characters_fails_validation(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('A', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'name',
            $validator->errors()->toArray()
        );
    }

    public function test_phone_longer_than_20_characters_fails_validation(): void
    {
        $validator = $this->validate([
            'phone' => str_repeat('1', 21),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'phone',
            $validator->errors()->toArray()
        );
    }

    public function test_address_longer_than_255_characters_fails_validation(): void
    {
        $validator = $this->validate([
            'address' => str_repeat('A', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'address',
            $validator->errors()->toArray()
        );
    }

    public function test_all_fields_are_optional(): void
    {
        $validator = $this->validate([]);

        $this->assertFalse($validator->fails());
    }
}
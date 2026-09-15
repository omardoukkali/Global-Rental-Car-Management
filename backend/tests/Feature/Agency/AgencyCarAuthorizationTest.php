<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\Car;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgencyCarAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createAgencyOwner(): array
    {
        $owner = User::factory()->create([
            'role' => 'agency',
            'status' => 'active',
        ]);

        $agency = Agency::factory()->approved()->create([
            'owner_id' => $owner->id,
        ]);

        return [$owner, $agency];
    }

    private function createCar(Agency $agency): Car
    {
        return $agency->cars()->create([
            'city_id' => City::factory()->create()->id,
            'brand' => 'Dacia',
            'model' => 'Logan',
            'year' => 2022,
            'color' => 'White',
            'plate_number' => 'SCRUM101-' . Str::upper(Str::random(8)),
            'type' => 'sedan',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.5,
            'status' => 'available',
        ]);
    }

    public function test_agency_cannot_view_another_agencys_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $car = $this->createCar($otherAgency);

        $this->actingAs($owner)
            ->getJson("/api/agency/cars/{$car->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'This car does not belong to your agency.');
    }

    public function test_agency_cannot_update_another_agencys_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $car = $this->createCar($otherAgency);

        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$car->id}", ['brand' => 'Tampered'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'This car does not belong to your agency.');

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'brand' => 'Dacia',
        ]);
    }

    public function test_agency_cannot_disable_another_agencys_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $car = $this->createCar($otherAgency);

        $this->actingAs($owner)
            ->patchJson("/api/agency/cars/{$car->id}/disable")
            ->assertStatus(403)
            ->assertJsonPath('message', 'This car does not belong to your agency.');

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'status' => 'available',
        ]);
    }
}

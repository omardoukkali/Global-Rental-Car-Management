<?php

namespace Tests\Feature\Scrum;

use App\Models\Agency;
use App\Models\AgencyPoint;
use App\Models\Car;
use App\Models\City;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Scrum114ReservationPointAgencyTest extends TestCase
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

    private function createClient(): User
    {
        return User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
    }

    private function createCar(Agency $agency): Car
    {
        return $agency->cars()->create([
            'city_id' => City::factory()->create()->id,
            'brand' => 'Dacia',
            'model' => 'Logan',
            'year' => 2022,
            'color' => 'White',
            'plate_number' => 'SCRUM114-' . Str::upper(Str::random(8)),
            'type' => 'sedan',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.5,
            'status' => 'available',
        ]);
    }

    private function createPoint(Agency $agency, string $name): AgencyPoint
    {
        return $agency->agencyPoints()->create([
            'city_id' => City::factory()->create()->id,
            'name' => $name,
            'address' => '1 Test Street',
            'allows_pickup' => true,
            'allows_return' => true,
            'is_active' => true,
        ]);
    }

    private function createReservation(
        User $client,
        Agency $agency,
        Car $car,
        AgencyPoint $pickupPoint,
        AgencyPoint $returnPoint
    ): Reservation {
        return Reservation::create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $agency->id,
            'pickup_point_id' => $pickupPoint->id,
            'return_point_id' => $returnPoint->id,
            'reference' => 'SCRUM114-' . Str::upper(Str::random(8)),
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(4),
            'daily_price_snapshot' => 250,
            'total_amount' => 500,
            'status' => 'pending',
        ]);
    }

    public function test_foreign_pickup_point_cannot_be_used_when_creating_reservation(): void
    {
        [, $agency] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $client = $this->createClient();
        $car = $this->createCar($agency);
        $ownPoint = $this->createPoint($agency, 'Own point');
        $foreignPoint = $this->createPoint($otherAgency, 'Foreign point');

        $this->actingAs($client)
            ->postJson('/api/reservations', [
                'car_id' => $car->id,
                'pickup_point_id' => $foreignPoint->id,
                'return_point_id' => $ownPoint->id,
                'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pickup point does not belong to the car agency.');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_foreign_return_point_cannot_be_used_when_creating_reservation(): void
    {
        [, $agency] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $client = $this->createClient();
        $car = $this->createCar($agency);
        $ownPoint = $this->createPoint($agency, 'Own point');
        $foreignPoint = $this->createPoint($otherAgency, 'Foreign point');

        $this->actingAs($client)
            ->postJson('/api/reservations', [
                'car_id' => $car->id,
                'pickup_point_id' => $ownPoint->id,
                'return_point_id' => $foreignPoint->id,
                'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Return point does not belong to the car agency.');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_foreign_point_cannot_be_used_when_updating_reservation(): void
    {
        [, $agency] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $client = $this->createClient();
        $car = $this->createCar($agency);
        $ownPickup = $this->createPoint($agency, 'Own pickup');
        $ownReturn = $this->createPoint($agency, 'Own return');
        $foreignPoint = $this->createPoint($otherAgency, 'Foreign point');
        $reservation = $this->createReservation(
            $client,
            $agency,
            $car,
            $ownPickup,
            $ownReturn
        );

        $this->actingAs($client)
            ->putJson("/api/reservations/{$reservation->id}", [
                'return_point_id' => $foreignPoint->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Return point does not belong to the reservation agency.');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'return_point_id' => $ownReturn->id,
        ]);
    }
}

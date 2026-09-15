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

class Scrum117ReservationVisibilityTest extends TestCase
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
            'plate_number' => 'SCRUM117-' . Str::upper(Str::random(8)),
            'type' => 'sedan',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.5,
            'status' => 'available',
        ]);
    }

    private function createPoint(Agency $agency): AgencyPoint
    {
        return $agency->agencyPoints()->create([
            'city_id' => City::factory()->create()->id,
            'name' => 'SCRUM-117 Point',
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
        string $status = 'pending'
    ): Reservation {
        $point = $this->createPoint($agency);

        return Reservation::create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $agency->id,
            'pickup_point_id' => $point->id,
            'return_point_id' => $point->id,
            'reference' => 'SCRUM117-' . Str::upper(Str::random(8)),
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(4),
            'daily_price_snapshot' => 250,
            'total_amount' => 500,
            'status' => $status,
        ]);
    }

    public function test_client_sees_only_its_own_reservations_in_the_index(): void
    {
        [, $agency] = $this->createAgencyOwner();
        $car = $this->createCar($agency);
        $client = $this->createClient();
        $otherClient = $this->createClient();
        $ownReservation = $this->createReservation($client, $agency, $car);
        $otherReservation = $this->createReservation($otherClient, $agency, $car);

        $response = $this->actingAs($client)
            ->getJson('/api/reservations')
            ->assertOk();

        $ids = collect($response->json('reservations'))->pluck('id')->all();

        $this->assertSame([$ownReservation->id], $ids);
        $this->assertNotContains($otherReservation->id, $ids);
    }

    public function test_client_cannot_view_another_clients_reservation(): void
    {
        [, $agency] = $this->createAgencyOwner();
        $car = $this->createCar($agency);
        $client = $this->createClient();
        $otherClient = $this->createClient();
        $otherReservation = $this->createReservation($otherClient, $agency, $car);

        $this->actingAs($client)
            ->getJson("/api/reservations/{$otherReservation->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to access this reservation.');
    }

    public function test_agency_can_act_on_a_reservation_for_its_car(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $car = $this->createCar($agency);
        $reservation = $this->createReservation($this->createClient(), $agency, $car, 'confirmed');

        $this->actingAs($owner)
            ->patchJson("/api/reservations/{$reservation->id}/pickup/confirm-agency")
            ->assertOk();

        $this->assertNotNull($reservation->fresh()->agency_pickup_confirmed_at);
    }

    public function test_agency_cannot_act_on_a_reservation_for_another_agencys_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $otherCar = $this->createCar($otherAgency);
        $reservation = $this->createReservation(
            $this->createClient(),
            $otherAgency,
            $otherCar,
            'confirmed'
        );

        $this->actingAs($owner)
            ->patchJson("/api/reservations/{$reservation->id}/pickup/confirm-agency")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to confirm pickup for this reservation.');
    }
}

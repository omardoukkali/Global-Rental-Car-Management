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

class Scrum165DisputedConfirmationTest extends TestCase
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

    private function createDisputedReservation(User $client, Agency $agency): Reservation
    {
        $city = City::factory()->create();
        $car = $agency->cars()->create([
            'city_id' => $city->id,
            'brand' => 'Dacia',
            'model' => 'Logan',
            'year' => 2022,
            'color' => 'White',
            'plate_number' => 'SCRUM165-' . Str::upper(Str::random(8)),
            'type' => 'sedan',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.5,
            'status' => 'available',
        ]);
        $point = $agency->agencyPoints()->create([
            'city_id' => $city->id,
            'name' => 'SCRUM-165 Point',
            'address' => '1 Test Street',
            'allows_pickup' => true,
            'allows_return' => true,
            'is_active' => true,
        ]);

        return Reservation::create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $agency->id,
            'pickup_point_id' => $point->id,
            'return_point_id' => $point->id,
            'reference' => 'SCRUM165-' . Str::upper(Str::random(8)),
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'daily_price_snapshot' => 250,
            'total_amount' => 500,
            'status' => 'disputed',
        ]);
    }

    public function test_client_cannot_confirm_pickup_after_reservation_is_disputed(): void
    {
        [, $agency] = $this->createAgencyOwner();
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $reservation = $this->createDisputedReservation($client, $agency);

        $this->actingAs($client)
            ->patchJson("/api/reservations/{$reservation->id}/pickup/confirm-client")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only confirmed reservations can confirm pickup.');
    }

    public function test_agency_cannot_confirm_pickup_after_reservation_is_disputed(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $reservation = $this->createDisputedReservation(
            User::factory()->create(['role' => 'client', 'status' => 'active']),
            $agency
        );

        $this->actingAs($owner)
            ->patchJson("/api/reservations/{$reservation->id}/pickup/confirm-agency")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only confirmed reservations can confirm pickup.');
    }

    public function test_client_cannot_confirm_return_after_reservation_is_disputed(): void
    {
        [, $agency] = $this->createAgencyOwner();
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $reservation = $this->createDisputedReservation($client, $agency);

        $this->actingAs($client)
            ->patchJson("/api/reservations/{$reservation->id}/return/confirm-client")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only picked up reservations can confirm return.');
    }

    public function test_agency_cannot_confirm_return_after_reservation_is_disputed(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $reservation = $this->createDisputedReservation(
            User::factory()->create(['role' => 'client', 'status' => 'active']),
            $agency
        );

        $this->actingAs($owner)
            ->patchJson("/api/reservations/{$reservation->id}/return/confirm-agency")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only picked up reservations can confirm return.');
    }
}

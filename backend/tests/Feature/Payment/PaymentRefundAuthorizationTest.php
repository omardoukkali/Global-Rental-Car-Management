<?php

namespace Tests\Feature\Payment;

use App\Models\Agency;
use App\Models\City;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentRefundAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createClient(): User
    {
        return User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
    }

    /**
     * Returns the owner and their approved agency.
     */
    private function createAgency(): array
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

    private function createReservation(User $client, Agency $agency, string $status): Reservation
    {
        $city = City::factory()->create();

        $car = $agency->cars()->create([
            'city_id' => $city->id,
            'brand' => 'Dacia',
            'model' => 'Logan',
            'year' => 2023,
            'plate_number' => 'AUTH-' . Str::upper(Str::random(8)),
            'type' => 'sedan',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 300,
            'energy_type' => 'diesel',
            'fuel_consumption' => 5.5,
            'status' => 'available',
        ]);

        $point = $agency->agencyPoints()->create([
            'city_id' => $city->id,
            'name' => 'Point',
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
            'reference' => 'AUTH-' . Str::upper(Str::random(8)),
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(8),
            'daily_price_snapshot' => 300,
            'total_amount' => 900,
            'status' => $status,
        ]);
    }

    private function createPayment(Reservation $reservation): Payment
    {
        return Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'commission_rate' => 15,
            'platform_commission' => 135,
            'agency_amount' => 765,
            'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    // --- POST /payments ---

    public function test_client_cannot_pay_another_clients_reservation(): void
    {
        [, $agency] = $this->createAgency();
        $owner = $this->createClient();
        $otherClient = $this->createClient();
        $reservation = $this->createReservation($owner, $agency, 'pending');

        $this->actingAs($otherClient)
            ->postJson('/api/payments', ['reservation_id' => $reservation->id])
            ->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to pay for this reservation.');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_agency_cannot_use_the_payment_endpoint(): void
    {
        [$owner, $agency] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'pending');

        $this->actingAs($owner)
            ->postJson('/api/payments', ['reservation_id' => $reservation->id])
            ->assertStatus(403);
    }

    public function test_payment_requires_authentication(): void
    {
        $this->postJson('/api/payments', ['reservation_id' => Str::uuid()->toString()])
            ->assertStatus(401);
    }

    // --- POST /refunds ---

    public function test_client_cannot_use_the_refund_endpoint(): void
    {
        [, $agency] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'confirmed');
        $payment = $this->createPayment($reservation);

        $this->actingAs($client)
            ->postJson('/api/refunds', ['payment_id' => $payment->id])
            ->assertStatus(403);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_agency_cannot_refund_a_payment_of_another_agency(): void
    {
        [, $agency] = $this->createAgency();
        [$otherOwner] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'confirmed');
        $payment = $this->createPayment($reservation);

        $this->actingAs($otherOwner)
            ->postJson('/api/refunds', ['payment_id' => $payment->id])
            ->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to refund this payment.');

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_agency_can_refund_a_payment_of_its_own_reservation(): void
    {
        [$owner, $agency] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'confirmed');
        $payment = $this->createPayment($reservation);

        $this->actingAs($owner)
            ->postJson('/api/refunds', ['payment_id' => $payment->id])
            ->assertStatus(201);

        $this->assertDatabaseCount('refunds', 1);
    }

    // --- PATCH /refunds/{refund}/decision ---

    public function test_agency_cannot_decide_a_refund_of_another_agency(): void
    {
        [, $agency] = $this->createAgency();
        [$otherOwner] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'cancelled');
        $payment = $this->createPayment($reservation);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'agency_id' => $agency->id,
            'percentage' => 50,
            'refunded_amount' => 450,
            'decision_source' => 'automatic',
            'status' => 'pending',
            'decided_at' => now(),
        ]);

        $this->actingAs($otherOwner)
            ->patchJson("/api/refunds/{$refund->id}/decision", ['percentage' => 100])
            ->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to decide this refund.');

        $this->assertSame('pending', $refund->fresh()->status);
    }

    public function test_client_cannot_decide_a_refund(): void
    {
        [, $agency] = $this->createAgency();
        $client = $this->createClient();
        $reservation = $this->createReservation($client, $agency, 'cancelled');
        $payment = $this->createPayment($reservation);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'agency_id' => $agency->id,
            'percentage' => 50,
            'refunded_amount' => 450,
            'decision_source' => 'automatic',
            'status' => 'pending',
            'decided_at' => now(),
        ]);

        $this->actingAs($client)
            ->patchJson("/api/refunds/{$refund->id}/decision", ['percentage' => 100])
            ->assertStatus(403);

        $this->assertSame('pending', $refund->fresh()->status);
    }
}

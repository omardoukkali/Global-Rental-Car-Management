<?php

namespace Tests\Feature\Admin;

use App\Models\Agency;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_user_cannot_view_admin_dashboard(): void
    {
        $this->getJson('/api/admin/dashboard')->assertStatus(401);
    }

    public function test_client_cannot_view_admin_dashboard(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->actingAs($client)
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_agency_role_cannot_view_admin_dashboard(): void
    {
        $owner = User::factory()->create([
            'role' => 'agency',
            'status' => 'active',
        ]);
        Agency::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_dashboard_returns_live_stats_and_lists(): void
    {
        $admin = $this->createAdmin();

        $approved = Agency::factory()->approved()->create([
            'name' => 'Luxury Auto',
            'avg_rating' => 4.5,
            'commission_rate' => 8,
        ]);
        $pending = Agency::factory()->create([
            'name' => 'Atlas Cars Casablanca',
            'email' => 'atlas@example.com',
            'phone' => '+212600000001',
            'address' => '12 Rue Atlas',
        ]);
        Agency::factory()->create([
            'name' => 'Riviera Rent',
            'address' => '',
        ]);
        Agency::factory()->rejected()->create();

        $car = Car::factory()->create([
            'agency_id' => $approved->id,
            'city_id' => $approved->city_id,
            'brand' => 'Tesla',
            'model' => 'Model 3',
        ]);

        $client = User::factory()->create([
            'first_name' => 'Youssef',
            'last_name' => 'Tazi',
            'role' => 'client',
        ]);

        $confirmed = Reservation::factory()->create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $approved->id,
            'status' => 'confirmed',
            'total_amount' => 6000,
            'reference' => 'RES-DASH01',
        ]);

        Payment::query()->create([
            'reservation_id' => $confirmed->id,
            'amount' => 6000,
            'commission_rate' => 8,
            'platform_commission' => 480,
            'agency_amount' => 5520,
            'transaction_id' => 'TXN-DASH-1',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $cancelled = Reservation::factory()->create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $approved->id,
            'status' => 'cancelled',
            'total_amount' => 1400,
            'reference' => 'RES-DASH02',
        ]);

        Payment::query()->create([
            'reservation_id' => $cancelled->id,
            'amount' => 1400,
            'commission_rate' => 8,
            'platform_commission' => 112,
            'agency_amount' => 1288,
            'transaction_id' => 'TXN-DASH-2',
            'status' => 'paid',
            'paid_at' => now()->subMonths(2),
        ]);

        Reservation::factory()->create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $approved->id,
            'status' => 'disputed',
            'reference' => 'RES-DASH03',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('stats.active_agencies', 1)
            ->assertJsonPath('stats.cars', 1)
            ->assertJsonPath('stats.reservations_this_month', 3)
            ->assertJsonPath('stats.platform_revenue', 480)
            ->assertJsonPath('stats.avg_rating', 4.5)
            ->assertJsonPath('stats.users', User::count());

        $pendingNames = collect($response->json('pending_agencies'))->pluck('name');
        $this->assertTrue($pendingNames->contains('Atlas Cars Casablanca'));
        $this->assertTrue($pendingNames->contains('Riviera Rent'));
        $this->assertCount(2, $pendingNames);

        $atlas = collect($response->json('pending_agencies'))
            ->firstWhere('name', 'Atlas Cars Casablanca');
        $this->assertSame($pending->id, $atlas['id']);
        $this->assertSame('Email', $atlas['checks'][0]['label']);
        $this->assertTrue($atlas['checks'][0]['valid']);

        $riviera = collect($response->json('pending_agencies'))
            ->firstWhere('name', 'Riviera Rent');
        $this->assertFalse(
            collect($riviera['checks'])->firstWhere('label', 'Adresse')['valid']
        );

        $recent = collect($response->json('recent_reservations'));
        $this->assertTrue($recent->contains(fn ($row) => $row['reference'] === 'RES-DASH01'));
        $confirmedRow = $recent->firstWhere('reference', 'RES-DASH01');
        $this->assertSame('Luxury Auto', $confirmedRow['agency']);
        $this->assertSame('Youssef T.', $confirmedRow['client']);
        $this->assertEquals(6000, $confirmedRow['amount']);
        $this->assertEquals(480, $confirmedRow['commission']);
        $this->assertSame('confirmed', $confirmedRow['status']);

        $cancelledRow = $recent->firstWhere('reference', 'RES-DASH02');
        $this->assertEquals(0, $cancelledRow['commission']);

        $this->assertCount(6, $response->json('monthly_revenue'));
        $currentMonth = collect($response->json('monthly_revenue'))
            ->firstWhere('month', now()->month);
        $this->assertEquals(480, $currentMonth['revenue']);

        $this->assertCount(1, $response->json('reports'));
        $this->assertSame('RES-DASH03', $response->json('reports.0.reference'));
        $this->assertSame('Tesla Model 3', $response->json('reports.0.car'));
        $this->assertSame('Litige sur la réservation', $response->json('reports.0.title'));
    }
}

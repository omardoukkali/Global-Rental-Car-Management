<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createAgency(): array
    {
        $city = City::create([
            'name' => 'Tangier',
            'region' => 'Tanger-Tetouan-Al Hoceima',
            'country' => 'Morocco',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'agency',
            'status' => 'active',
        ]);

        $agency = Agency::create([
            'owner_id' => $user->id,
            'city_id' => $city->id,
            'name' => 'Test Agency',
            'slug' => 'test-agency',
            'address' => 'Tangier, Morocco',
            'phone' => '+212600000000',
            'email' => 'agency@test.com',
            'status' => 'approved',
            'commission_rate' => 15,
            'avg_rating' => 0,
            'total_reviews' => 0,
        ]);

        return [$user, $agency];
    }

    public function test_agency_can_get_its_profile(): void
    {
        [$user, $agency] = $this->createAgency();

        $response = $this->actingAs($user)
            ->getJson('/api/agency/profile');

        $response->assertStatus(200)
            ->assertJsonPath('agency.id', $agency->id)
            ->assertJsonPath('agency.name', 'Test Agency')
            ->assertJsonPath('agency.email', 'agency@test.com');
    }

    public function test_agency_can_update_its_profile(): void
    {
        [$user, $agency] = $this->createAgency();

        $response = $this->actingAs($user)
            ->putJson('/api/agency/profile', [
                'name' => 'Updated Agency',
                'email' => 'updated@test.com',
                'phone' => '+212611111111',
                'address' => 'Casablanca, Morocco',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('agency.name', 'Updated Agency')
            ->assertJsonPath('agency.email', 'updated@test.com')
            ->assertJsonPath('agency.phone', '+212611111111')
            ->assertJsonPath('agency.address', 'Casablanca, Morocco');

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => 'Updated Agency',
            'email' => 'updated@test.com',
            'phone' => '+212611111111',
            'address' => 'Casablanca, Morocco',
        ]);
    }

    public function test_unauthenticated_user_cannot_get_agency_profile(): void
    {
        $response = $this->getJson('/api/agency/profile');

        $response->assertStatus(401);
    }

    public function test_client_cannot_get_agency_profile(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($client)
            ->getJson('/api/agency/profile');

        $response->assertStatus(403);
    }

    public function test_invalid_agency_profile_data_returns_422(): void
    {
        [$user] = $this->createAgency();

        $response = $this->actingAs($user)
            ->putJson('/api/agency/profile', [
                'name' => str_repeat('A', 256),
                'email' => 'invalid-email',
                'phone' => str_repeat('1', 21),
                'address' => str_repeat('A', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'address',
            ]);
    }

    public function test_agency_can_partially_update_its_profile(): void
    {
        [$user, $agency] = $this->createAgency();

        $response = $this->actingAs($user)
            ->putJson('/api/agency/profile', [
                'phone' => '+212622222222',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'phone' => '+212622222222',
            'name' => 'Test Agency',
        ]);
    }
}
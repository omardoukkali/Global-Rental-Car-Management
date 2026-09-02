<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAgencyTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }
    /**
     * Crée un user agency approuvé, avec son agence liée.
     */
    private function createApprovedAgencyUser(): User

    {

        $user = User::factory()->create([

            'role'   => 'agency',

            'status' => 'active',

        ]);

        Agency::factory()->approved()->create([

            'owner_id' => $user->id,

        ]);

        return $user->fresh();

    }

    /**
     * TEST #1 - Cas nominal : le propriétaire met à jour son agence.
     */
    public function test_agency_owner_can_update_own_agency(): void
    {
        $user = $this->createApprovedAgencyUser();

        $response = $this->actingAs($user)->putJson('/api/agency/profile', [
            'name'    => 'Nouveau Nom Agence',
            'phone'   => '+212555000111',
            'address' => '12 rue Mohammed V, Casablanca',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'agency' => ['id', 'name', 'phone', 'address'],
        ]);

        $this->assertDatabaseHas('agencies', [
            'id'      => $user->agency->id,
            'name'    => 'Nouveau Nom Agence',
            'phone'   => '+212555000111',
            'address' => '12 rue Mohammed V, Casablanca',
        ]);
    }

    /**
     * TEST #2 - Mise à jour partielle (un seul champ).
     */
    public function test_agency_owner_can_update_only_one_field(): void
    {
        $user = $this->createApprovedAgencyUser();
        $originalPhone = $user->agency->phone;

        $response = $this->actingAs($user)->putJson('/api/agency/profile', [
            'name' => 'Nom Modifié',
        ]);

        $response->assertStatus(200);
        // Le nom a changé, mais le téléphone est resté le même
        $this->assertEquals('Nom Modifié', $user->agency->fresh()->name);
        $this->assertEquals($originalPhone, $user->agency->fresh()->phone);
    }

    /**
     * TEST #3 - Un utilisateur non authentifié reçoit 401.
     */
    public function test_unauthenticated_user_cannot_update_agency(): void
    {
        $response = $this->putJson('/api/agency/profile', [
            'name' => 'Tentative',
        ]);

        $response->assertStatus(401);
    }

    /**
     * TEST #4 - Un client (rôle non-agency) reçoit 403.
     */
    public function test_client_cannot_update_agency(): void
    {
        $client = User::factory()->create([
            'role'   => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($client)->putJson('/api/agency/profile', [
            'name' => 'Tentative Hacker',
        ]);

        $response->assertStatus(403);
    }

    /**
     * TEST #5 - Email invalide → 422.
     */
    public function test_update_fails_with_invalid_email(): void
    {
        $user = $this->createApprovedAgencyUser();

        $response = $this->actingAs($user)->putJson('/api/agency/profile', [
            'email' => 'pas-un-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * TEST #6 - Nom trop long (> 255 caractères) → 422.
     */
    public function test_update_fails_with_name_too_long(): void
    {
        $user = $this->createApprovedAgencyUser();

        $response = $this->actingAs($user)->putJson('/api/agency/profile', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * TEST #7 - Requête vide → 200 (les champs sont "sometimes").
     */
    public function test_update_with_empty_body_succeeds(): void
    {
        $user = $this->createApprovedAgencyUser();
        $originalName = $user->agency->name;

        $response = $this->actingAs($user)->putJson('/api/agency/profile', []);

        $response->assertStatus(200);
        $this->assertEquals($originalName, $user->agency->fresh()->name);
    }
}

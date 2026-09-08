<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\AgencyPoint;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests d'intégration — CRUD des points d'agence (agency locations).
 *
 * Parcourt toute la chaîne HTTP : routing -> middleware
 * (auth:sanctum, role:agency, agency.approved) -> route model binding ->
 * FormRequest (validation) -> contrôleur -> persistance PostgreSQL.
 *
 * Endpoints couverts :
 *   - POST   /api/agency/points                       (create)
 *   - GET    /api/agency/points                       (read : liste)
 *   - GET    /api/agency/points/{agencyPoint}         (read : détail)
 *   - PUT    /api/agency/points/{agencyPoint}         (update)
 *   - PATCH  /api/agency/points/{agencyPoint}/toggle-status  (delete logique)
 *
 * NB : il n'existe pas de suppression physique ; toggle-status
 * (activation / désactivation) tient lieu de « delete » logique.
 */
class AgencyPointCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    /**
     * Crée un propriétaire (rôle agency) et son agence dans l'état donné.
     *
     * @return array{0: User, 1: Agency}
     */
    private function createAgencyOwner(string $status = 'approved'): array
    {
        $owner = User::factory()->create([
            'role'   => 'agency',
            'status' => 'active',
        ]);

        $agency = Agency::factory()->create([
            'owner_id' => $owner->id,
            'status'   => $status,
        ]);

        return [$owner->fresh(), $agency];
    }

    /**
     * Crée un point rattaché à l'agence donnée.
     */
    private function makePoint(Agency $agency, array $overrides = []): AgencyPoint
    {
        return $agency->agencyPoints()->create(array_merge([
            'city_id'       => City::factory()->create()->id,
            'name'          => 'Existing Point',
            'address'       => '1 Rue de Test, Tanger',
            'allows_pickup' => true,
            'allows_return' => true,
            'is_active'     => true,
        ], $overrides));
    }

    /**
     * Payload de création valide.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'city_id'       => City::factory()->create()->id,
            'name'          => 'Downtown Pickup Point',
            'address'       => '12 Boulevard Mohammed V, Tanger',
            'latitude'      => 35.7595000,
            'longitude'     => -5.8340000,
            'allows_pickup' => true,
            'allows_return' => true,
            'opening_hours' => ['mon' => '09:00-18:00'],
            'instructions'  => 'Sonner à la grille principale.',
        ], $overrides);
    }

    // =====================================================================
    //  Garde-fous d'authentification / d'autorisation
    // =====================================================================

    /**
     * TEST #1 - Un utilisateur non authentifié est refusé → 401.
     */
    public function test_unauthenticated_user_cannot_access_points(): void
    {
        $this->getJson('/api/agency/points')->assertStatus(401);
        $this->postJson('/api/agency/points', $this->validPayload())
            ->assertStatus(401);
    }

    /**
     * TEST #2 - Un client ne peut pas accéder aux points d'agence → 403.
     */
    public function test_client_cannot_access_points(): void
    {
        $client = User::factory()->create([
            'role'   => 'client',
            'status' => 'active',
        ]);

        $this->actingAs($client)
            ->getJson('/api/agency/points')
            ->assertStatus(403);
    }

    /**
     * TEST #3 - Une agence non encore approuvée est bloquée → 403.
     */
    public function test_pending_agency_cannot_access_points(): void
    {
        [$owner] = $this->createAgencyOwner('pending');

        $this->actingAs($owner)
            ->getJson('/api/agency/points')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');
    }

    /**
     * TEST #4 - Un utilisateur « agency » sans profil d'agence → 403.
     */
    public function test_agency_user_without_profile_is_blocked(): void
    {
        $orphan = User::factory()->create([
            'role'   => 'agency',
            'status' => 'active',
        ]);

        $this->actingAs($orphan)
            ->getJson('/api/agency/points')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Agency profile not found.');
    }

    // =====================================================================
    //  CREATE
    // =====================================================================

    /**
     * TEST #5 - Création d'un point valide → 201 + persistance + defaults.
     */
    public function test_approved_agency_can_create_point(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $payload = $this->validPayload();

        $response = $this->actingAs($owner)
            ->postJson('/api/agency/points', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Agency point created successfully.')
            ->assertJsonPath('point.name', $payload['name'])
            ->assertJsonPath('point.address', $payload['address'])
            ->assertJsonPath('point.agency_id', $agency->id)
            ->assertJsonPath('point.city_id', $payload['city_id'])
            ->assertJsonPath('point.allows_pickup', true)
            ->assertJsonPath('point.allows_return', true);

        // is_active n'est pas fourni à la création : la valeur par défaut BDD
        // (true) est appliquée côté base, on la vérifie donc en base.

        $this->assertDatabaseHas('agency_points', [
            'agency_id' => $agency->id,
            'city_id'   => $payload['city_id'],
            'name'      => $payload['name'],
            'address'   => $payload['address'],
            'is_active' => true,
        ]);
    }

    /**
     * TEST #6 - Champs requis manquants → 422 + erreurs de validation.
     */
    public function test_create_fails_when_required_fields_missing(): void
    {
        [$owner] = $this->createAgencyOwner();

        $this->actingAs($owner)
            ->postJson('/api/agency/points', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'city_id',
                'name',
                'address',
            ]);

        $this->assertDatabaseCount('agency_points', 0);
    }

    /**
     * TEST #7 - Ni pickup ni return autorisés → 422 (règle métier `after`).
     */
    public function test_create_fails_when_neither_pickup_nor_return_allowed(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'allows_pickup' => false,
            'allows_return' => false,
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/points', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['allows_pickup']);

        $this->assertDatabaseCount('agency_points', 0);
    }

    /**
     * TEST #8 - city_id inexistant → 422 (règle exists:cities,id).
     */
    public function test_create_fails_with_unknown_city(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'city_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/points', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_id']);
    }

    /**
     * TEST #9 - Latitude hors bornes → 422.
     */
    public function test_create_fails_with_out_of_range_latitude(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'latitude' => 120, // hors [-90, 90]
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/points', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    // =====================================================================
    //  READ (index + show)
    // =====================================================================

    /**
     * TEST #10 - L'index ne renvoie que les points de l'agence courante.
     */
    public function test_index_returns_only_own_points(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $this->makePoint($agency, ['name' => 'Mine A']);
        $this->makePoint($agency, ['name' => 'Mine B']);

        // Points appartenant à une autre agence : ne doivent pas fuiter.
        [, $otherAgency] = $this->createAgencyOwner();
        $this->makePoint($otherAgency, ['name' => 'Not Mine']);

        $response = $this->actingAs($owner)
            ->getJson('/api/agency/points');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'points')
            ->assertJsonPath('points.0.agency_id', $agency->id)
            ->assertJsonPath('points.1.agency_id', $agency->id);
    }

    /**
     * TEST #11 - show renvoie un point appartenant à l'agence → 200.
     */
    public function test_show_returns_own_point(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $point = $this->makePoint($agency, ['name' => 'Airport Desk']);

        $this->actingAs($owner)
            ->getJson("/api/agency/points/{$point->id}")
            ->assertStatus(200)
            ->assertJsonPath('point.id', $point->id)
            ->assertJsonPath('point.name', 'Airport Desk');
    }

    /**
     * TEST #12 - show sur le point d'une autre agence → 403.
     */
    public function test_show_forbidden_for_foreign_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $foreign = $this->makePoint($otherAgency);

        $this->actingAs($owner)
            ->getJson("/api/agency/points/{$foreign->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'This point does not belong to your agency.');
    }

    /**
     * TEST #13 - show avec un UUID inconnu → 404 (route model binding).
     */
    public function test_show_returns_404_for_unknown_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        $unknownId = (string) Str::uuid();

        $this->actingAs($owner)
            ->getJson("/api/agency/points/{$unknownId}")
            ->assertStatus(404);
    }

    // =====================================================================
    //  UPDATE
    // =====================================================================

    /**
     * TEST #14 - Mise à jour partielle d'un point → 200 + persistance.
     */
    public function test_update_own_point(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $point = $this->makePoint($agency, [
            'name'    => 'Old Name',
            'address' => 'Old Address',
        ]);

        $response = $this->actingAs($owner)
            ->putJson("/api/agency/points/{$point->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Agency point updated successfully.')
            ->assertJsonPath('point.name', 'New Name')
            // Champ non fourni : inchangé (règles `sometimes`).
            ->assertJsonPath('point.address', 'Old Address');

        $this->assertDatabaseHas('agency_points', [
            'id'      => $point->id,
            'name'    => 'New Name',
            'address' => 'Old Address',
        ]);
    }

    /**
     * TEST #15 - Mise à jour du point d'une autre agence → 403.
     */
    public function test_update_forbidden_for_foreign_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $foreign = $this->makePoint($otherAgency, ['name' => 'Foreign']);

        $this->actingAs($owner)
            ->putJson("/api/agency/points/{$foreign->id}", [
                'name' => 'Hacked',
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'This point does not belong to your agency.');

        $this->assertDatabaseHas('agency_points', [
            'id'   => $foreign->id,
            'name' => 'Foreign',
        ]);
    }

    /**
     * TEST #16 - Mise à jour invalide (pickup+return à false) → 422.
     */
    public function test_update_fails_with_invalid_data(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $point = $this->makePoint($agency);

        $this->actingAs($owner)
            ->putJson("/api/agency/points/{$point->id}", [
                'allows_pickup' => false,
                'allows_return' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['allows_pickup']);
    }

    /**
     * TEST #17 - Mise à jour d'un point inexistant → 404.
     */
    public function test_update_returns_404_for_unknown_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        $unknownId = (string) Str::uuid();

        $this->actingAs($owner)
            ->putJson("/api/agency/points/{$unknownId}", [
                'name' => 'Whatever',
            ])
            ->assertStatus(404);
    }

    // =====================================================================
    //  DELETE logique (toggle-status)
    // =====================================================================

    /**
     * TEST #18 - Désactivation puis réactivation d'un point → 200 + persistance.
     */
    public function test_toggle_status_activates_and_deactivates_point(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $point = $this->makePoint($agency, ['is_active' => true]);

        // Actif -> inactif.
        $this->actingAs($owner)
            ->patchJson("/api/agency/points/{$point->id}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Agency point deactivated successfully.')
            ->assertJsonPath('point.is_active', false);

        $this->assertDatabaseHas('agency_points', [
            'id'        => $point->id,
            'is_active' => false,
        ]);

        // Inactif -> actif.
        $this->actingAs($owner)
            ->patchJson("/api/agency/points/{$point->id}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Agency point activated successfully.')
            ->assertJsonPath('point.is_active', true);

        $this->assertDatabaseHas('agency_points', [
            'id'        => $point->id,
            'is_active' => true,
        ]);
    }

    /**
     * TEST #19 - toggle-status sur le point d'une autre agence → 403.
     */
    public function test_toggle_status_forbidden_for_foreign_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $foreign = $this->makePoint($otherAgency, ['is_active' => true]);

        $this->actingAs($owner)
            ->patchJson("/api/agency/points/{$foreign->id}/toggle-status")
            ->assertStatus(403)
            ->assertJsonPath('message', 'This point does not belong to your agency.');

        $this->assertDatabaseHas('agency_points', [
            'id'        => $foreign->id,
            'is_active' => true,
        ]);
    }


    public function test_toggle_status_returns_404_for_unknown_point(): void
    {
        [$owner] = $this->createAgencyOwner();
        $unknownId = (string) Str::uuid();

        $this->actingAs($owner)
            ->patchJson("/api/agency/points/{$unknownId}/toggle-status")
            ->assertStatus(404);
    }
}

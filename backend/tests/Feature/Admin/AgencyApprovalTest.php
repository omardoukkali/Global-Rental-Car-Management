<?php

namespace Tests\Feature\Admin;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests d'intégration — Workflow d'approbation des agences.
 *
 * Parcourt toute la chaîne HTTP : routing -> middleware (auth:sanctum, role:admin,
 * agency.approved) -> route model binding -> contrôleur -> persistance en base.
 * Vérifie aussi l'effet en aval : une agence approuvée débloque les routes
 * réservées aux agences approuvées pour son propriétaire.
 */
class AgencyApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }

    /**
     * Crée un administrateur.
     */
    private function createAdmin(): User
    {
        return User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * Crée un propriétaire (rôle agency) avec son agence liée dans l'état donné.
     *
     * @return array{0: User, 1: Agency}
     */
    private function createAgencyWithOwner(string $status = 'pending'): array
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

    // =====================================================================
    //  Garde-fous d'authentification / d'autorisation
    // =====================================================================

    /**
     * TEST #1 - Un utilisateur non authentifié ne peut pas approuver → 401.
     */
    public function test_unauthenticated_user_cannot_approve_agency(): void
    {
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(401);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST #2 - Un utilisateur non authentifié ne peut pas rejeter → 401.
     */
    public function test_unauthenticated_user_cannot_reject_agency(): void
    {
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->patchJson("/api/admin/agencies/{$agency->id}/reject");

        $response->assertStatus(401);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST #3 - Un client ne peut pas approuver → 403.
     */
    public function test_client_cannot_approve_agency(): void
    {
        $client = User::factory()->create([
            'role'   => 'client',
            'status' => 'active',
        ]);
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->actingAs($client)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST #4 - Une agence (rôle non-admin) ne peut pas approuver → 403.
     */
    public function test_agency_role_cannot_approve_agency(): void
    {
        [$owner, $agency] = $this->createAgencyWithOwner();

        $response = $this->actingAs($owner)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST #5 - Un client ne peut pas rejeter → 403.
     */
    public function test_client_cannot_reject_agency(): void
    {
        $client = User::factory()->create([
            'role'   => 'client',
            'status' => 'active',
        ]);
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->actingAs($client)
            ->patchJson("/api/admin/agencies/{$agency->id}/reject");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'pending',
        ]);
    }

    // =====================================================================
    //  Cas nominaux
    // =====================================================================

    /**
     * TEST #6 - Un admin approuve une agence en attente → 200 + persistance.
     */
    public function test_admin_can_approve_pending_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Agency approved successfully.')
            ->assertJsonPath('agency.id', $agency->id)
            ->assertJsonPath('agency.status', 'approved');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'approved',
        ]);
    }

    /**
     * TEST #7 - Un admin rejette une agence en attente → 200 + persistance.
     */
    public function test_admin_can_reject_pending_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Agency rejected successfully.')
            ->assertJsonPath('agency.id', $agency->id)
            ->assertJsonPath('agency.status', 'rejected');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'rejected',
        ]);
    }

    // =====================================================================
    //  Machine à états : seules les agences "pending" sont modifiables
    // =====================================================================

    /**
     * TEST #8 - Impossible d'approuver une agence déjà approuvée → 422.
     */
    public function test_cannot_approve_already_approved_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner('approved');

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending agencies can be approved.');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'approved',
        ]);
    }

    /**
     * TEST #9 - Impossible d'approuver une agence rejetée → 422.
     */
    public function test_cannot_approve_rejected_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner('rejected');

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending agencies can be approved.');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * TEST #10 - Impossible de rejeter une agence déjà rejetée → 422.
     */
    public function test_cannot_reject_already_rejected_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner('rejected');

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/reject");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending agencies can be rejected.');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * TEST #11 - Impossible de rejeter une agence approuvée → 422.
     */
    public function test_cannot_reject_approved_agency(): void
    {
        $admin = $this->createAdmin();
        [, $agency] = $this->createAgencyWithOwner('approved');

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/reject");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending agencies can be rejected.');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agency->id,
            'status' => 'approved',
        ]);
    }

    // =====================================================================
    //  Route model binding
    // =====================================================================

    /**
     * TEST #12 - Un UUID d'agence inexistant → 404.
     */
    public function test_approve_returns_404_for_unknown_agency(): void
    {
        $admin = $this->createAdmin();
        $unknownId = (string) Str::uuid();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$unknownId}/approve");

        $response->assertStatus(404);
    }

    // =====================================================================
    //  Workflow complet : effet de l'approbation sur les routes en aval
    // =====================================================================

    /**
     * TEST #13 - Le propriétaire est bloqué tant que l'agence est en attente,
     * puis débloqué une fois l'agence approuvée par l'admin.
     */
    public function test_owner_gains_access_to_approved_routes_after_approval(): void
    {
        $admin = $this->createAdmin();
        [$owner, $agency] = $this->createAgencyWithOwner();

        // Avant approbation : la route réservée aux agences approuvées est bloquée.
        $before = $this->actingAs(User::find($owner->id))
            ->getJson('/api/agency/points');
        $before->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');

        // L'admin approuve l'agence.
        $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/approve")
            ->assertStatus(200);

        // Après approbation : le propriétaire accède désormais à la route.
        $after = $this->actingAs(User::find($owner->id))
            ->getJson('/api/agency/points');
        $after->assertStatus(200)
            ->assertJsonStructure(['points']);
    }

    /**
     * TEST #14 - Après un rejet, le propriétaire reste bloqué sur les routes
     * réservées aux agences approuvées.
     */
    public function test_owner_stays_blocked_from_approved_routes_after_rejection(): void
    {
        $admin = $this->createAdmin();
        [$owner, $agency] = $this->createAgencyWithOwner();

        $this->actingAs($admin)
            ->patchJson("/api/admin/agencies/{$agency->id}/reject")
            ->assertStatus(200);

        $response = $this->actingAs(User::find($owner->id))
            ->getJson('/api/agency/points');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');
    }
}

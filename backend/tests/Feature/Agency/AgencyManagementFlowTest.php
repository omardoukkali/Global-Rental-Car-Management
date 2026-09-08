<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test d'intégration bout-en-bout (E2E) — Parcours complet de gestion d'agence.
 *
 * Contrairement aux tests CRUD (qui isolent une ressource via actingAs), ce test
 * rejoue le VRAI parcours applicatif de bout en bout, avec de vrais jetons Sanctum
 * obtenus par /api/login, en passant par toute la pile HTTP :
 *
 *   1. Inscription publique de l'agence      (POST /api/register/agency)
 *   2. Connexion du propriétaire             (POST /api/login → token)
 *   3. Accès refusé tant que l'agence est    (GET/POST /api/agency/points → 403)
 *      en attente d'approbation
 *   4. Connexion admin + approbation         (PATCH /api/admin/agencies/{id}/approve)
 *   5. Lecture du profil agence              (GET  /api/agency/profile)
 *   6. Mise à jour du profil agence          (PUT  /api/agency/profile)
 *   7. Création d'un point d'agence          (POST /api/agency/points)
 *   8. Listing des points                    (GET  /api/agency/points)
 *   9. Déconnexion + révocation du token     (POST /api/logout → 401 ensuite)
 *
 * Plusieurs acteurs (visiteur, propriétaire d'agence, administrateur) et plusieurs
 * ressources sont chaînés dans un même scénario : c'est la définition d'un test E2E.
 */
class AgencyManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER_PASSWORD = 'Password123';
    private const ADMIN_PASSWORD = 'Password123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    /**
     * Données d'inscription agence valides (le propriétaire + l'agence).
     */
    private function agencyRegistrationData(City $city, array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Omar',
            'last_name'             => 'Doukkali',
            'email'                 => 'owner@automaroc.ma',
            'phone'                 => '+212612345678',
            'password'              => self::OWNER_PASSWORD,
            'password_confirmation' => self::OWNER_PASSWORD,
            'agency_name'           => 'AutoMaroc Location',
            'agency_city'           => $city->id,
            'address'               => '15 Bd Mohammed V, Tanger',
            'agency_phone'          => '+212539123456',
        ], $overrides);
    }

    /**
     * Connecte un utilisateur via l'endpoint réel et renvoie son jeton.
     */
    private function login(string $email, string $password): string
    {
        $response = $this->postJson('/api/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token']);

        return $response->json('token');
    }

    /**
     * Crée un administrateur et renvoie son jeton d'authentification.
     */
    private function loginAsAdmin(): string
    {
        User::factory()->create([
            'email'    => 'admin@grcm.ma',
            'password' => self::ADMIN_PASSWORD,
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        return $this->login('admin@grcm.ma', self::ADMIN_PASSWORD);
    }

    /**
     * Prépare la requête suivante avec ce jeton Bearer.
     *
     * On vide d'abord le cache des guards. En test, toutes les requêtes
     * partagent le même conteneur applicatif, et le RequestGuard de Sanctum
     * met en cache le PREMIER utilisateur résolu. Sans ce reset, changer
     * d'acteur en cours de test resterait sans effet (le premier utilisateur
     * authentifié demeurerait actif). En conditions réelles, chaque requête
     * HTTP est un process neuf : ce reset reproduit fidèlement ce comportement.
     */
    private function actingWithToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    // =====================================================================
    //  Scénario principal : cycle de vie complet, chemin nominal
    // =====================================================================

    /**
     * TEST #1 - Parcours E2E complet de la gestion d'une agence.
     */
    public function test_full_agency_management_lifecycle(): void
    {
        $city = City::factory()->create();

        // --- Étape 1 : inscription publique de l'agence -------------------
        $register = $this->postJson(
            '/api/register/agency',
            $this->agencyRegistrationData($city)
        );

        $register->assertStatus(201)
            ->assertJsonPath('message', 'Agency registered successfully and is awaiting approval.')
            ->assertJsonPath('agency.status', 'pending');

        $agencyId = $register->json('agency.id');

        $this->assertDatabaseHas('users', [
            'email' => 'owner@automaroc.ma',
            'role'  => 'agency',
        ]);
        $this->assertDatabaseHas('agencies', [
            'id'     => $agencyId,
            'status' => 'pending',
        ]);

        // --- Étape 2 : connexion du propriétaire → jeton ------------------
        $ownerToken = $this->login('owner@automaroc.ma', self::OWNER_PASSWORD);

        // --- Étape 3 : accès bloqué tant que l'agence n'est pas approuvée --
        $this->actingWithToken($ownerToken)
            ->getJson('/api/agency/points')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');

        $this->actingWithToken($ownerToken)
            ->postJson('/api/agency/points', [
                'city_id'       => $city->id,
                'name'          => 'Premature Point',
                'address'       => 'Nowhere',
                'allows_pickup' => true,
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');

        // --- Étape 4 : un admin approuve l'agence -------------------------
        $adminToken = $this->loginAsAdmin();

        $this->actingWithToken($adminToken)
            ->patchJson("/api/admin/agencies/{$agencyId}/approve")
            ->assertStatus(200)
            ->assertJsonPath('agency.status', 'approved');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agencyId,
            'status' => 'approved',
        ]);

        // --- Étape 5 : le propriétaire lit son profil (même jeton) --------
        // Le jeton créé avant approbation reste valide : le middleware relit
        // le statut à chaque requête, l'accès est donc désormais débloqué.
        $this->actingWithToken($ownerToken)
            ->getJson('/api/agency/profile')
            ->assertStatus(200)
            ->assertJsonPath('agency.id', $agencyId)
            ->assertJsonPath('agency.name', 'AutoMaroc Location');

        // --- Étape 6 : mise à jour du profil agence -----------------------
        $this->actingWithToken($ownerToken)
            ->putJson('/api/agency/profile', [
                'name'    => 'AutoMaroc Premium',
                'phone'   => '+212539999999',
                'address' => 'Zone Franche, Tanger',
            ])
            ->assertStatus(200)
            ->assertJsonPath('agency.name', 'AutoMaroc Premium')
            ->assertJsonPath('agency.phone', '+212539999999');

        $this->assertDatabaseHas('agencies', [
            'id'      => $agencyId,
            'name'    => 'AutoMaroc Premium',
            'address' => 'Zone Franche, Tanger',
        ]);

        // --- Étape 7 : création d'un point d'agence -----------------------
        $created = $this->actingWithToken($ownerToken)
            ->postJson('/api/agency/points', [
                'city_id'       => $city->id,
                'name'          => 'Agence Centre-Ville',
                'address'       => '12 Bd Pasteur, Tanger',
                'allows_pickup' => true,
                'allows_return' => true,
            ]);

        $created->assertStatus(201)
            ->assertJsonPath('message', 'Agency point created successfully.')
            ->assertJsonPath('point.agency_id', $agencyId)
            ->assertJsonPath('point.name', 'Agence Centre-Ville');

        $this->assertDatabaseHas('agency_points', [
            'agency_id' => $agencyId,
            'name'      => 'Agence Centre-Ville',
        ]);

        // --- Étape 8 : listing des points de l'agence ---------------------
        $this->actingWithToken($ownerToken)
            ->getJson('/api/agency/points')
            ->assertStatus(200)
            ->assertJsonCount(1, 'points')
            ->assertJsonPath('points.0.name', 'Agence Centre-Ville');

        // --- Étape 9 : déconnexion → le jeton est révoqué -----------------
        $this->actingWithToken($ownerToken)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Logout successful.');

        $this->actingWithToken($ownerToken)
            ->getJson('/api/agency/points')
            ->assertStatus(401);
    }

    // =====================================================================
    //  Scénario alternatif : agence rejetée
    // =====================================================================

    /**
     * TEST #2 - Une agence rejetée n'obtient jamais l'accès à la gestion.
     */
    public function test_rejected_agency_never_gains_management_access(): void
    {
        $city = City::factory()->create();

        $register = $this->postJson(
            '/api/register/agency',
            $this->agencyRegistrationData($city, [
                'email' => 'reject-me@agency.ma',
            ])
        );
        $register->assertStatus(201);
        $agencyId = $register->json('agency.id');

        // L'admin rejette l'agence.
        $adminToken = $this->loginAsAdmin();
        $this->actingWithToken($adminToken)
            ->patchJson("/api/admin/agencies/{$agencyId}/reject")
            ->assertStatus(200)
            ->assertJsonPath('agency.status', 'rejected');

        // Le propriétaire reste bloqué sur les routes de gestion des points.
        $ownerToken = $this->login('reject-me@agency.ma', self::OWNER_PASSWORD);

        $this->actingWithToken($ownerToken)
            ->getJson('/api/agency/points')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');

        $this->assertDatabaseHas('agencies', [
            'id'     => $agencyId,
            'status' => 'rejected',
        ]);
    }

    // =====================================================================
    //  Scénario transverse : cohérence des jetons
    // =====================================================================

    /**
     * TEST #3 - Un jeton révoqué (logout) ne donne plus accès au profil,
     * mais une reconnexion rétablit un accès pleinement fonctionnel.
     */
    public function test_logout_revokes_then_relogin_restores_access(): void
    {
        $city = City::factory()->create();

        $agencyId = $this->postJson(
            '/api/register/agency',
            $this->agencyRegistrationData($city, ['email' => 'cycle@agency.ma'])
        )->json('agency.id');

        // Approbation par l'admin pour débloquer les routes.
        $adminToken = $this->loginAsAdmin();
        $this->actingWithToken($adminToken)
            ->patchJson("/api/admin/agencies/{$agencyId}/approve")
            ->assertStatus(200);

        // 1re session : accès OK, puis logout.
        $firstToken = $this->login('cycle@agency.ma', self::OWNER_PASSWORD);
        $this->actingWithToken($firstToken)
            ->getJson('/api/agency/profile')
            ->assertStatus(200);
        $this->actingWithToken($firstToken)
            ->postJson('/api/logout')
            ->assertStatus(200);
        $this->actingWithToken($firstToken)
            ->getJson('/api/agency/profile')
            ->assertStatus(401);

        // 2e session : un nouveau jeton rétablit l'accès.
        $secondToken = $this->login('cycle@agency.ma', self::OWNER_PASSWORD);
        $this->actingWithToken($secondToken)
            ->getJson('/api/agency/profile')
            ->assertStatus(200)
            ->assertJsonPath('agency.id', $agencyId);
    }
}

<?php

namespace Tests\Feature\Car;

use App\Models\Agency;
use App\Models\Car;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests d'intégration — CRUD des voitures (parc d'une agence).
 *
 * Parcourt toute la chaîne HTTP : routing -> middleware
 * (auth:sanctum, role:agency, agency.approved) -> route model binding ->
 * FormRequest (validation + cohérence énergétique) -> contrôleur ->
 * persistance PostgreSQL.
 *
 * Endpoints couverts :
 *   - POST   /api/agency/cars                    (create)
 *   - PUT    /api/agency/cars/{car}              (update)
 *   - PATCH  /api/agency/cars/{car}/disable      (désactivation logique)
 *
 * NB : il n'existe pas de suppression physique. La désactivation bascule
 * le `status` à « unavailable » (le modèle utilise aussi SoftDeletes, mais
 * ce endpoint ne fait pas de soft-delete : il change uniquement le statut).
 */
class CarCrudTest extends TestCase
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
     * Crée une voiture rattachée à l'agence donnée (diesel par défaut).
     */
    private function makeCar(Agency $agency, array $overrides = []): Car
    {
        return $agency->cars()->create(array_merge([
            'city_id'          => City::factory()->create()->id,
            'brand'            => 'Dacia',
            'model'            => 'Logan',
            'year'             => 2021,
            'color'            => 'Blanc',
            'plate_number'     => 'TNG-' . Str::upper(Str::random(6)),
            'type'             => 'sedan',
            'transmission'     => 'manual',
            'seats'            => 5,
            'daily_price'      => 250.00,
            'energy_type'      => 'diesel',
            'fuel_consumption' => 5.50,
            'electric_range'   => null,
            'status'           => 'available',
        ], $overrides));
    }

    /**
     * Payload de création valide (véhicule essence).
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'city_id'          => City::factory()->create()->id,
            'brand'            => 'Renault',
            'model'            => 'Clio',
            'year'             => 2022,
            'color'            => 'Gris',
            'plate_number'     => 'TNG-12345-A',
            'type'             => 'hatchback',
            'transmission'     => 'manual',
            'seats'            => 5,
            'daily_price'      => 300.00,
            'energy_type'      => 'gasoline',
            'fuel_consumption' => 6.20,
            // electric_range omis : interdit pour un véhicule essence.
        ], $overrides);
    }

    // =====================================================================
    //  Garde-fous d'authentification / d'autorisation
    // =====================================================================

    /**
     * TEST #1 - Un utilisateur non authentifié est refusé → 401.
     */
    public function test_unauthenticated_user_cannot_manage_cars(): void
    {
        $this->postJson('/api/agency/cars', $this->validPayload())
            ->assertStatus(401);
    }

    /**
     * TEST #2 - Un client ne peut pas gérer les voitures → 403.
     */
    public function test_client_cannot_manage_cars(): void
    {
        $client = User::factory()->create([
            'role'   => 'client',
            'status' => 'active',
        ]);

        $this->actingAs($client)
            ->postJson('/api/agency/cars', $this->validPayload())
            ->assertStatus(403);
    }

    /**
     * TEST #3 - Une agence non encore approuvée est bloquée → 403.
     */
    public function test_pending_agency_cannot_manage_cars(): void
    {
        [$owner] = $this->createAgencyOwner('pending');

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $this->validPayload())
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your agency is awaiting approval.');

        $this->assertDatabaseCount('cars', 0);
    }

    // =====================================================================
    //  CREATE
    // =====================================================================

    /**
     * TEST #4 - Création d'une voiture valide → 201 + persistance +
     *           statut par défaut « available » + rattachement à l'agence.
     */
    public function test_approved_agency_can_create_car(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $payload = $this->validPayload();

        $response = $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Car created successfully.')
            ->assertJsonPath('car.brand', 'Renault')
            ->assertJsonPath('car.model', 'Clio')
            ->assertJsonPath('car.plate_number', 'TNG-12345-A')
            ->assertJsonPath('car.agency_id', $agency->id);

        // status non fourni : le défaut « available » est appliqué côté BDD.
        // L'instance renvoyée juste après create() n'est pas rechargée, donc
        // elle ne le connaît pas encore — on le vérifie en base, pas via JSON.

        $this->assertDatabaseHas('cars', [
            'agency_id'    => $agency->id,
            'plate_number' => 'TNG-12345-A',
            'brand'        => 'Renault',
            'model'        => 'Clio',
            'energy_type'  => 'gasoline',
            'status'       => 'available',
        ]);
    }

    /**
     * TEST #5 - Création d'un véhicule électrique cohérent → 201.
     *           electric_range requis, fuel_consumption doit rester null.
     */
    public function test_can_create_electric_car(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'plate_number'     => 'TNG-EV-001',
            'energy_type'      => 'electric',
            'fuel_consumption' => null,
            'electric_range'   => 420,
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload)
            ->assertStatus(201)
            ->assertJsonPath('car.energy_type', 'electric');

        $this->assertDatabaseHas('cars', [
            'agency_id'        => $agency->id,
            'plate_number'     => 'TNG-EV-001',
            'energy_type'      => 'electric',
            'electric_range'   => 420,
            'fuel_consumption' => null,
        ]);
    }

    /**
     * TEST #6 - Champs requis manquants → 422 + aucune persistance.
     */
    public function test_create_fails_when_required_fields_missing(): void
    {
        [$owner] = $this->createAgencyOwner();

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'city_id',
                'brand',
                'model',
                'year',
                'plate_number',
                'type',
                'transmission',
                'seats',
                'daily_price',
                'energy_type',
            ]);

        $this->assertDatabaseCount('cars', 0);
    }

    /**
     * TEST #7 - Plaque déjà utilisée → 422 (règle unique:cars,plate_number).
     */
    public function test_create_fails_with_duplicate_plate_number(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $this->makeCar($agency, ['plate_number' => 'TNG-DUP-01']);

        $payload = $this->validPayload(['plate_number' => 'TNG-DUP-01']);

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plate_number']);

        // La voiture d'origine reste seule en base.
        $this->assertDatabaseCount('cars', 1);
    }

    /**
     * TEST #8 - city_id inexistant → 422 (règle exists:cities,id).
     */
    public function test_create_fails_with_unknown_city(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload(['city_id' => (string) Str::uuid()]);

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_id']);
    }

    /**
     * TEST #9 - Incohérence énergétique : essence sans consommation → 422.
     */
    public function test_create_fails_when_gasoline_without_fuel_consumption(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'energy_type'      => 'gasoline',
            'fuel_consumption' => null,
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fuel_consumption']);

        $this->assertDatabaseCount('cars', 0);
    }

    /**
     * TEST #10 - Incohérence énergétique : essence avec autonomie électrique → 422.
     */
    public function test_create_fails_when_gasoline_has_electric_range(): void
    {
        [$owner] = $this->createAgencyOwner();

        $payload = $this->validPayload([
            'energy_type'      => 'gasoline',
            'fuel_consumption' => 6.20,
            'electric_range'   => 300, // interdit pour un thermique
        ]);

        $this->actingAs($owner)
            ->postJson('/api/agency/cars', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['electric_range']);
    }

    // =====================================================================
    //  UPDATE
    // =====================================================================

    /**
     * TEST #11 - Mise à jour partielle d'une voiture → 200 + persistance.
     *            Les champs non fournis restent inchangés (règles `sometimes`).
     */
    public function test_update_own_car(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $car = $this->makeCar($agency, [
            'brand'       => 'Dacia',
            'daily_price' => 250.00,
        ]);

        $response = $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$car->id}", [
                'daily_price' => 320.00,
                'color'       => 'Noir',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Car updated successfully.')
            ->assertJsonPath('car.color', 'Noir')
            // Champ non fourni : inchangé.
            ->assertJsonPath('car.brand', 'Dacia');

        $this->assertDatabaseHas('cars', [
            'id'          => $car->id,
            'brand'       => 'Dacia',
            'color'       => 'Noir',
            'daily_price' => 320.00,
        ]);
    }

    /**
     * TEST #12 - Changement d'énergie cohérent (diesel → électrique) → 200.
     *            electric_range fourni, fuel_consumption remis à null.
     */
    public function test_update_can_switch_energy_type_consistently(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $car = $this->makeCar($agency, [
            'energy_type'      => 'diesel',
            'fuel_consumption' => 5.50,
            'electric_range'   => null,
        ]);

        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$car->id}", [
                'energy_type'      => 'electric',
                'fuel_consumption' => null,
                'electric_range'   => 380,
            ])
            ->assertStatus(200)
            ->assertJsonPath('car.energy_type', 'electric');

        $this->assertDatabaseHas('cars', [
            'id'               => $car->id,
            'energy_type'      => 'electric',
            'electric_range'   => 380,
            'fuel_consumption' => null,
        ]);
    }

    /**
     * TEST #13 - Mise à jour incohérente (électrique sans autonomie) → 422.
     */
    public function test_update_fails_with_inconsistent_energy(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $car = $this->makeCar($agency);

        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$car->id}", [
                'energy_type' => 'electric', // autonomie non fournie
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['electric_range']);
    }

    /**
     * TEST #14 - La plaque peut rester identique à la sienne (self ignoré),
     *            mais pas prendre celle d'une autre voiture → 422.
     */
    public function test_update_plate_number_uniqueness(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $carA = $this->makeCar($agency, ['plate_number' => 'TNG-AAA-11']);
        $carB = $this->makeCar($agency, ['plate_number' => 'TNG-BBB-22']);

        // Reprendre sa propre plaque : autorisé.
        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$carB->id}", [
                'plate_number' => 'TNG-BBB-22',
            ])
            ->assertStatus(200);

        // Prendre la plaque de carA : refusé.
        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$carB->id}", [
                'plate_number' => 'TNG-AAA-11',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plate_number']);

        $this->assertDatabaseHas('cars', [
            'id'           => $carB->id,
            'plate_number' => 'TNG-BBB-22',
        ]);
    }

    /**
     * TEST #15 - Mise à jour de la voiture d'une autre agence → 403.
     */
    public function test_update_forbidden_for_foreign_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $foreign = $this->makeCar($otherAgency, ['brand' => 'Peugeot']);

        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$foreign->id}", [
                'brand' => 'Hacked',
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'This car does not belong to your agency.');

        $this->assertDatabaseHas('cars', [
            'id'    => $foreign->id,
            'brand' => 'Peugeot',
        ]);
    }

    /**
     * TEST #16 - Mise à jour d'une voiture inexistante → 404 (route model binding).
     */
    public function test_update_returns_404_for_unknown_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        $unknownId = (string) Str::uuid();

        $this->actingAs($owner)
            ->putJson("/api/agency/cars/{$unknownId}", [
                'brand' => 'Whatever',
            ])
            ->assertStatus(404);
    }

    // =====================================================================
    //  DÉSACTIVATION (disable)
    // =====================================================================

    /**
     * TEST #17 - Désactivation d'une voiture disponible → 200 + status
     *            « unavailable » persisté en base.
     */
    public function test_disable_own_car(): void
    {
        [$owner, $agency] = $this->createAgencyOwner();
        $car = $this->makeCar($agency, ['status' => 'available']);

        $this->actingAs($owner)
            ->patchJson("/api/agency/cars/{$car->id}/disable")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Car disabled successfully.')
            ->assertJsonPath('car.status', 'unavailable');

        $this->assertDatabaseHas('cars', [
            'id'     => $car->id,
            'status' => 'unavailable',
        ]);

        // La voiture n'est PAS soft-deletée : elle reste en base, seul le
        // statut change.
        $this->assertDatabaseCount('cars', 1);
        $this->assertNotSoftDeleted('cars', ['id' => $car->id]);
    }

    /**
     * TEST #18 - Désactivation de la voiture d'une autre agence → 403 +
     *            statut inchangé en base.
     */
    public function test_disable_forbidden_for_foreign_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        [, $otherAgency] = $this->createAgencyOwner();
        $foreign = $this->makeCar($otherAgency, ['status' => 'available']);

        $this->actingAs($owner)
            ->patchJson("/api/agency/cars/{$foreign->id}/disable")
            ->assertStatus(403)
            ->assertJsonPath('message', 'This car does not belong to your agency.');

        $this->assertDatabaseHas('cars', [
            'id'     => $foreign->id,
            'status' => 'available',
        ]);
    }

    /**
     * TEST #19 - Désactivation d'une voiture inexistante → 404.
     */
    public function test_disable_returns_404_for_unknown_car(): void
    {
        [$owner] = $this->createAgencyOwner();
        $unknownId = (string) Str::uuid();

        $this->actingAs($owner)
            ->patchJson("/api/agency/cars/{$unknownId}/disable")
            ->assertStatus(404);
    }
}

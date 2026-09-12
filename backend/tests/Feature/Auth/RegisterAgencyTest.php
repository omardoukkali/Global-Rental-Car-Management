<?php

namespace Tests\Feature\Auth;

use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterAgencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper : construit des données valides d'inscription agence.
     *
     * On isole ça dans une méthode pour éviter la duplication.
     * Chaque test peut ensuite surcharger juste les champs qui l'intéressent.
     */
    private function validAgencyData(array $overrides = []): array
    {
        // On crée une ville en DB (nécessaire car agency_city doit exister)
        $city = City::factory()->create();

        return array_merge([
            'first_name' => 'Omar',
            'last_name' => 'Doukkali',
            'email' => 'owner@automaroc.ma',
            'phone' => '+212612345678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'agency_name' => 'AutoMaroc Location',
            'agency_city' => $city->id, // UUID valide
            'address' => '15 Bd Mohammed V, Tanger',
            'agency_phone' => '+212539123456',
        ], $overrides);
    }

    /**
     * TEST #1 - Cas nominal : une agence peut s'inscrire.
     */
    public function test_agency_can_register_with_valid_data(): void
    {
        $data = $this->validAgencyData();

        $response = $this->postJson('/api/register/agency', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'first_name', 'last_name', 'email', 'role'],
            'agency' => ['id', 'name', 'slug', 'address', 'status'],
        ]);

        // Le user doit être créé avec le rôle 'agency'
        $this->assertDatabaseHas('users', [
            'email' => 'owner@automaroc.ma',
            'role' => 'agency',
        ]);

        // L'agence doit être créée en DB
        $this->assertDatabaseHas('agencies', [
            'name' => 'AutoMaroc Location',
            'address' => '15 Bd Mohammed V, Tanger',
        ]);
    }

    /**
     * TEST #2 - L'agence est créée avec le status 'pending' (en attente de validation).
     */
    public function test_agency_has_pending_status_after_registration(): void
    {
        $data = $this->validAgencyData();

        $this->postJson('/api/register/agency', $data);

        $agency = Agency::where('name', 'AutoMaroc Location')->first();
        $this->assertNotNull($agency);
        $this->assertEquals('pending', $agency->status);
    }

    /**
     * TEST #3 - L'inscription échoue si agency_city n'existe pas dans la table cities.
     */
    public function test_agency_registration_fails_if_city_does_not_exist(): void
    {
        // On génère un UUID valide mais qui n'existe PAS en DB
        $data = $this->validAgencyData([
            'agency_city' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response = $this->postJson('/api/register/agency', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['agency_city']);
    }

    /**
     * TEST #4 - L'inscription échoue si agency_city n'est pas un UUID valide.
     */
    public function test_agency_registration_fails_if_city_is_not_uuid(): void
    {
        $data = $this->validAgencyData([
            'agency_city' => 'Tanger', // <-- string au lieu d'UUID
        ]);

        $response = $this->postJson('/api/register/agency', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['agency_city']);
    }

    /**
     * TEST #5 - L'inscription échoue si le phone est manquant (required pour agency).
     */
    public function test_agency_registration_fails_if_phone_missing(): void
    {
        $data = $this->validAgencyData();
        unset($data['phone']);

        $response = $this->postJson('/api/register/agency', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    /**
     * TEST #6 - L'inscription échoue si des champs agence requis manquent.
     */
    public function test_agency_registration_fails_if_agency_fields_missing(): void
    {
        // On envoie uniquement les champs user, pas les champs agence
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+212612345678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register/agency', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'agency_name',
            'agency_city',
            'address',
            'agency_phone',
        ]);
    }

    /**
     * TEST #7 - L'inscription agence est atomique : si l'agence échoue, le user n'est pas créé.
     *
     * C'est important car AuthController::registerAgency utilise DB::transaction().
     * Si on triche en envoyant un UUID de city invalide APRÈS validation,
     * ça ne devrait rien créer.
     * (Ici, la validation le rattrape avant, mais le test vérifie qu'aucune trace ne reste.)
     */
    public function test_agency_registration_creates_nothing_if_validation_fails(): void
    {
        $data = $this->validAgencyData([
            'agency_city' => '00000000-0000-0000-0000-000000000000',
        ]);

        $this->postJson('/api/register/agency', $data);

        // Ni le user ni l'agence ne doivent exister
        $this->assertDatabaseMissing('users', ['email' => 'owner@automaroc.ma']);
        $this->assertDatabaseMissing('agencies', ['name' => 'AutoMaroc Location']);
    }
}

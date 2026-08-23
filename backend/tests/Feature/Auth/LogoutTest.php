<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST #1 - Un user connecté peut se déconnecter.
     *
     * Vérifie que :
     * - L'endpoint renvoie 200
     * - Le token utilisé est bien supprimé de la table personal_access_tokens
     */
    public function test_authenticated_user_can_logout(): void
    {
        // Arrange : on crée un user et on génère un token pour lui
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Vérification préalable : le token existe bien avant le logout
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Act : on envoie la requête POST /api/logout avec le Bearer token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logout successful.',
        ]);

        // Le token doit avoir été supprimé de la DB
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * TEST #2 - Le logout échoue si le user n'est pas authentifié (401).
     *
     * L'endpoint est protégé par le middleware auth:sanctum,
     * donc sans token, Laravel doit renvoyer 401.
     */
    public function test_logout_fails_if_not_authenticated(): void
    {
        // Act : appel sans header Authorization
        $response = $this->postJson('/api/logout');

        // Assert
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * TEST #3 - Le logout échoue avec un token invalide (401).
     */
    public function test_logout_fails_with_invalid_token(): void
    {
        // Act : on envoie un token bidon
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-xyz')
            ->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * TEST #4 - Le logout ne supprime QUE le token utilisé pour la requête,
     * pas tous les tokens du user.
     *
     * Utile si un user est connecté sur plusieurs appareils (mobile + web) :
     * se déconnecter sur mobile ne doit pas déconnecter le web.
     */
    public function test_logout_only_deletes_current_token_not_all_user_tokens(): void
    {
        // Arrange : un user avec 3 tokens (comme s'il était connecté sur 3 appareils)
        $user = User::factory()->create();
        $token1 = $user->createToken('device_mobile')->plainTextToken;
        $token2 = $user->createToken('device_web')->plainTextToken;
        $token3 = $user->createToken('device_tablet')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 3);

        // Act : logout avec le token 1 (mobile)
        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/logout');

        $response->assertStatus(200);

        // Assert : il reste 2 tokens (web + tablet)
        $this->assertDatabaseCount('personal_access_tokens', 2);
    }
}

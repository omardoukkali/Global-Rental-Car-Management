<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST #1 - Cas nominal : un user peut se connecter et recevoir un token.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange : on crée un user avec un password connu
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'Password123',
        ]);

        // Assert : status 200 + réponse contient un token + le user
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'email', 'role'],
        ]);

        // Le token doit être une string non vide
        $this->assertNotEmpty($response->json('token'));

        // Le token doit exister en DB (table personal_access_tokens de Sanctum)
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
        ]);
    }

    /**
     * TEST #2 - Login normalise l'email (trim + lowercase) — voir LoginRequest.
     */
    public function test_login_normalizes_email_case_and_spaces(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        // On envoie l'email avec des majuscules et des espaces
        $response = $this->postJson('/api/login', [
            'email' => '  USER@Example.COM  ',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * TEST #3 - Le login échoue avec un mauvais password (401 Unauthorized).
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Invalid email or password.',
        ]);

        // Aucun token ne doit avoir été créé
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * TEST #4 - Le login échoue avec un email inexistant (401 Unauthorized).
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'ghost@example.com',
            'password' => 'AnyPassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Invalid email or password.',
        ]);
    }

    /**
     * TEST #5 - Le login échoue si le compte est suspendu (403 Forbidden).
     */
    public function test_login_fails_if_account_suspended(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('Password123'),
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'suspended@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Your account has been suspended.',
        ]);

        // Aucun token ne doit avoir été créé
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * TEST #6 - Le login échoue si des champs requis manquent (validation 422).
     */
    public function test_login_fails_if_required_fields_missing(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * TEST #7 - Le login échoue si l'email n'est pas au bon format (validation 422).
     */
    public function test_login_fails_if_email_invalid(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}

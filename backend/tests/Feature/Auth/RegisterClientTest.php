<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterClientTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST #1 - Cas nominal.
     */
    public function test_client_can_register_with_valid_data(): void
    {
        $data = [
            'first_name' => 'Mehdi',
            'last_name' => 'El Fassi',
            'email' => 'mehdi@example.com',
            'phone' => '+212612345678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'first_name', 'last_name', 'email', 'role', 'status'],
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'mehdi@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);
        $user = User::where('email', 'mehdi@example.com')->first();
        $this->assertNotEquals('Password123', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    /**
     * TEST #2 - Le phone est optionnel pour un client.
     */
    public function test_client_can_register_without_phone(): void
    {
        $data = [
            'first_name' => 'Sara',
            'last_name' => 'Amrani',
            'email' => 'sara@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'sara@example.com',
            'phone' => null,
        ]);
    }

    /**
     * TEST #3 - L'inscription échoue si l'email est déjà utilisé.
     */
    public function test_client_registration_fails_if_email_already_exists(): void
    {
        // Arrange : on crée un user existant avec cet email
        User::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'first_name' => 'Ahmed',
            'last_name' => 'Bennis',
            'email' => 'existing@example.com', // <-- déjà pris
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register/client', $data);

        // 422 = Unprocessable Entity (validation Laravel)
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * TEST #4 - L'inscription échoue si le mot de passe n'est pas confirmé.
     */
    public function test_client_registration_fails_if_password_not_confirmed(): void
    {
        $data = [
            'first_name' => 'Karim',
            'last_name' => 'Alami',
            'email' => 'karim@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Different123', // <-- ne correspond pas
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * TEST #5 - L'inscription échoue si le mot de passe est trop court.
     */
    public function test_client_registration_fails_if_password_too_short(): void
    {
        $data = [
            'first_name' => 'Fatima',
            'last_name' => 'Zahraoui',
            'email' => 'fatima@example.com',
            'password' => 'Ab1', // <-- 3 caractères, min = 8
            'password_confirmation' => 'Ab1',
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * TEST #6 - L'inscription échoue si le mot de passe n'a que des chiffres (pas de lettres).
     */
    public function test_client_registration_fails_if_password_has_no_letters(): void
    {
        $data = [
            'first_name' => 'Youssef',
            'last_name' => 'Idrissi',
            'email' => 'youssef@example.com',
            'password' => '12345678', // <-- 8 chiffres mais 0 lettre
            'password_confirmation' => '12345678',
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * TEST #7 - L'inscription échoue si l'email n'est pas valide.
     */
    public function test_client_registration_fails_if_email_invalid(): void
    {
        $data = [
            'first_name' => 'Nadia',
            'last_name' => 'Chraibi',
            'email' => 'not-an-email', // <-- format invalide
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * TEST #8 - L'inscription échoue si des champs requis manquent.
     */
    public function test_client_registration_fails_if_required_fields_missing(): void
    {
        $data = [
            // first_name, last_name, email, password : tous manquants
        ];

        $response = $this->postJson('/api/register/client', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);
    }
}

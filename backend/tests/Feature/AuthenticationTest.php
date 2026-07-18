<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest can view login and register pages.
     */
    public function test_guest_can_view_auth_pages(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /**
     * Test client registration.
     * Note: Client registration validator does not require password_confirmation.
     */
    public function test_client_can_register(): void
    {
        $payload = [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@test.com',
            'phone' => '+123456789',
            'password' => 'password123',
        ];

        $response = $this->post('/register/client', $payload);

        // Verify redirect to landing page
        $response->assertRedirect(route('landing'));
        $response->assertSessionHas('success');

        // Verify database entry
        $this->assertDatabaseHas('users', [
            'email' => 'alice@test.com',
            'role' => 'client',
            'status' => 'active'
        ]);

        // Verify user is authenticated
        $this->assertAuthenticated();
    }

    /**
     * Test agency owner registration.
     * Note: Agency registration validator requires password_confirmation.
     */
    public function test_agency_owner_can_register(): void
    {
        // 1. Create a city
        $city = City::create([
            'id' => (string) \Illuminate\Support\Str::uuid(), // Primary key field
            'name' => 'Rabat',
            'region' => 'Rabat-Salé-Kénitra',
            'country' => 'Morocco',
            'is_active' => true
        ]);

        $payload = [
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@test.com',
            'phone' => '+987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agency_name' => 'Bob Car Rental',
            'agency_city' => $city->id,
            'address' => '123 Agency Road',
            'agency_phone' => '+555444333',
        ];

        $response = $this->post('/register/agency', $payload);

        // Redirects to login
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Verify user
        $this->assertDatabaseHas('users', [
            'email' => 'bob@test.com',
            'role' => 'agency_owner'
        ]);

        // Verify agency (status should be pending)
        $this->assertDatabaseHas('agencies', [
            'name' => 'Bob Car Rental',
            'phone' => '+555444333',
            'status' => 'pending'
        ]);

        // Verify owner is NOT logged in automatically
        $this->assertGuest();
    }

    /**
     * Test valid login and redirections based on role.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        // User model automatically hashes the password attribute on save (via 'password' => 'hashed' cast)
        // and generates the UUID (via HasUuids trait)
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@test.com',
            'phone' => '+111222333',
            'password' => 'password123',
            'role' => 'client',
            'status' => 'active'
        ]);

        $response = $this->post('/login', [
            'email' => 'client@test.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect(route('landing'));
        $this->assertAuthenticated();
        $this->assertEquals((string) $client->id, (string) auth()->id());
    }

    /**
     * Test invalid login credentials.
     */
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.com',
            'phone' => '+123',
            'password' => 'password123',
            'role' => 'client',
            'status' => 'active'
        ]);

        $response = $this->actingAs($user)->post('/auth/logout');

        $response->assertRedirect(route('landing'));
        $this->assertGuest();
    }

    /**
     * Server-side Security Boundary: Client cannot access agency owner dashboard.
     */
    public function test_client_cannot_access_agency_dashboard(): void
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@test.com',
            'phone' => '+123',
            'password' => 'password123',
            'role' => 'client',
            'status' => 'active'
        ]);

        $response = $this->actingAs($client)->get('/owner/dashboard');

        // Bounced to client reservations with error warning
        $response->assertRedirect(route('client.reservations'));
        $response->assertSessionHas('error');
    }

    /**
     * Server-side Security Boundary: Client cannot access admin control panel.
     */
    public function test_client_cannot_access_admin_dashboard(): void
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@test.com',
            'phone' => '+123',
            'password' => 'password123',
            'role' => 'client',
            'status' => 'active'
        ]);

        $response = $this->actingAs($client)->get('/admin/dashboard');

        $response->assertRedirect(route('client.reservations'));
        $response->assertSessionHas('error');
    }

    /**
     * Server-side Security Boundary: Agency Owner cannot access admin control panel.
     */
    public function test_agency_owner_cannot_access_admin_dashboard(): void
    {
        $owner = User::create([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@test.com',
            'phone' => '+123',
            'password' => 'password123',
            'role' => 'agency_owner',
            'status' => 'active'
        ]);

        $response = $this->actingAs($owner)->get('/admin/dashboard');

        $response->assertRedirect(route('agency.dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * Server-side Security Boundary: Guest is prompted to log in when trying to access client dashboard.
     */
    public function test_guest_cannot_access_client_dashboard(): void
    {
        $response = $this->get('/client/reservations');

        // Bounced to login
        $response->assertRedirect(route('login'));
    }
}

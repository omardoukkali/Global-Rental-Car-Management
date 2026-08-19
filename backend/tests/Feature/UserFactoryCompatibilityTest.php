<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFactoryCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_create_with_legacy_name_attribute_uses_current_schema(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'client',
            'status' => 'active',
        ]);
    }
}

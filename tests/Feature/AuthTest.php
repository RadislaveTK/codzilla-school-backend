<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Parent User',
            'email' => 'parent@example.com',
            'phone' => '+77001234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'parent@example.com')
            ->assertJsonPath('user.role', 'parent')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'parent@example.com',
            'role' => 'parent',
        ]);
    }

    public function test_registered_parent_can_login(): void
    {
        User::factory()->create([
            'email' => 'parent@example.com',
            'password' => Hash::make('password123'),
            'role' => 'parent',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'parent@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'parent@example.com')
            ->assertJsonStructure(['token']);
    }
}

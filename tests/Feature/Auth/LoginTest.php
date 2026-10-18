<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = \App\Models\User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = \App\Models\User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();

        $response->assertRedirect('/login');
    }
}
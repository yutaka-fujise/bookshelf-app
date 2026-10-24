<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_page(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_user_can_register_with_valid_information(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_displays_japanese_validation_messages(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください。',
            'email' => 'メールアドレスはメール形式で入力してください',
            'password' => 'パスワードは8文字以上で入力してください。',
        ]);

        $this->assertGuest();
    }

    public function test_registration_rejects_invalid_input_boundaries(): void
    {
        $cases = [
            ['name', str_repeat('あ', 256)],
            ['email', str_repeat('a', 250) . '@example.com'],
            ['password', '1234567'],
        ];

        foreach ($cases as [$field, $invalidValue]) {
            $data = [
                'name' => 'テストユーザー',
                'email' => 'valid@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $data[$field] = $invalidValue;

            if ($field === 'password') {
                $data['password_confirmation'] = $invalidValue;
            }

            $response = $this->post('/register', $data);

            $response->assertSessionHasErrors($field);
        }

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}

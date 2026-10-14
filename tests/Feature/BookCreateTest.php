<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_book_create_page(): void
    {
        $response = $this->get('/books/create');

        $response->assertRedirect(route('login'));
    }
}
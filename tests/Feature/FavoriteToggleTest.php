<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_toggle_favorite(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入りテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000003',
            'published_at' => '2026-10-14',
        ]);

        // お気に入り登録
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // お気に入り解除
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::create([
            'title' => 'ゲスト用テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000004',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
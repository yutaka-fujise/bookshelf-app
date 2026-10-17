<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000005',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テスト書籍');
        $response->assertSee('テスト著者');
    }

    public function test_nonexistent_book_returns_404(): void
    {
        $response = $this->get('/books/99999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_view_book_detail(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'ログイン用テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000006',
            'published_at' => '2026-10-14',
        ]);
        $response = $this->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('ログイン用テスト書籍');
    }

    public function test_guest_can_view_book_detail_with_review(): void
    {
        $reviewer = User::factory()->create();

        $book = Book::create([
            'title' => 'レビュー付きテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000015',
            'published_at' => '2026-10-14',
        ]);

        $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'レビュー表示テスト',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('レビュー付きテスト書籍');
        $response->assertSee('レビュー表示テスト');
    }

    public function test_authenticated_user_can_view_book_detail_with_review(): void
    {
        $reviewer = User::factory()->create();
        $viewer = User::factory()->create();

        $book = Book::create([
            'title' => 'ログイン時レビュー付き書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000016',
            'published_at' => '2026-10-14',
        ]);

        $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'ログイン時レビュー表示テスト',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('ログイン時レビュー表示テスト');
        $response->assertSee('いいね (0)');
    }
}
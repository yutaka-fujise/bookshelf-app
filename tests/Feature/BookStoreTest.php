<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-10-14',
            'description' => 'テスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);
        $book = \App\Models\Book::where('isbn13', '9784000000000')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect();
    }

    public function test_guest_cannot_store_book(): void
    {
        $response = $this->post('/books', [
            'title' => '未ログインの書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-10-14',
            'genres' => [1],
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'isbn13' => '9784000000001',
        ]);
    }

    public function test_book_store_requires_mandatory_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'genres',
        ]);

        $response->assertSessionDoesntHaveErrors('published_date');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_can_be_stored_without_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => '出版日任意テストジャンル',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '出版日未入力の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000003',
            'published_date' => '',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn13' => '9784000000003',
            'published_at' => null,
        ]);
    }

    public function test_book_store_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();

        \App\Models\Book::create([
            'user_id' => $user->id,
            'title' => '登録済み書籍',
            'author' => '既存の著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '重複書籍',
            'author' => '新しい著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-10-14',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_store_rejects_invalid_isbn_length(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '桁数不正の書籍',
            'author' => 'テスト著者',
            'isbn' => '123456789012', // 12桁
            'published_date' => '2026-10-14',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_store_rejects_nonexistent_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'ジャンル不正の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-10-14',
            'genres' => [99999],
        ]);

        $response->assertSessionHasErrors('genres.0');

        $this->assertDatabaseCount('books', 0);
    }
}

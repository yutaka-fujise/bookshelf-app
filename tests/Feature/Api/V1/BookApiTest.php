<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_book_index_returns_json_successfully(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_book_show_returns_book_with_genres_and_reviews(): void
    {
        $book = \App\Models\Book::create([
            'title' => 'API詳細テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000099',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'genres',
                    'reviews',
                ],
            ]);
    }

    public function test_nonexistent_book_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
    }

    public function test_book_index_can_filter_by_keyword(): void
    {
        \App\Models\Book::create([
            'title' => '検索対象の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000100',
            'published_at' => '2026-10-14',
        ]);

        \App\Models\Book::create([
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn13' => '9784000000101',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=検索対象');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '検索対象の書籍');
    }

    public function test_book_index_can_filter_by_genre(): void
    {
        $genre = \App\Models\Genre::create([
            'name' => 'APIテスト用ジャンル',
        ]);

        $matchingBook = \App\Models\Book::create([
            'title' => 'ジャンル一致書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000102',
            'published_at' => '2026-10-14',
        ]);

        $otherBook = \App\Models\Book::create([
            'title' => 'ジャンル不一致書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000103',
            'published_at' => '2026-10-14',
        ]);

        $matchingBook->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books?genre_id={$genre->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBook->id);
    }

    public function test_book_index_returns_pagination_information(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            \App\Models\Book::create([
                'title' => "ページテスト書籍{$i}",
                'author' => 'テスト著者',
                'isbn13' => sprintf('97840000002%02d', $i),
                'published_at' => '2026-10-14',
            ]);
        }

        $response = $this->getJson('/api/v1/books?page=2&per_page=2');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3);
    }

    public function test_book_index_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_book_index_rejects_invalid_genre_id(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id']);
    }

    public function test_book_index_rejects_keyword_over_255_characters(): void
    {
        $response = $this->getJson('/api/v1/books?keyword=' . str_repeat('a', 256));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['keyword']);
    }

    public function test_guest_cannot_store_book(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => '未認証テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000200',
            'published_date' => '2026-10-21',
            'genres' => [1],
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_store_book(): void
    {
        $user = \App\Models\User::factory()->create();

        $genre = \App\Models\Genre::create([
            'name' => 'API登録テストジャンル',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/books', [
            'title' => 'API登録テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000300',
            'published_date' => '2026-10-21',
            'description' => 'API登録テスト',
            'genres' => [$genre->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'API登録テスト書籍')
            ->assertJsonPath('data.genres.0.id', $genre->id);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API登録テスト書籍',
            'isbn13' => '9784000000300',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $response->json('data.id'),
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_store_requires_mandatory_fields(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/books', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }

    public function test_book_store_rejects_duplicate_isbn(): void
    {
        $user = \App\Models\User::factory()->create();

        \App\Models\Book::create([
            'title' => '登録済み書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000400',
            'published_at' => '2026-10-21',
        ]);

        $genre = \App\Models\Genre::create([
            'name' => 'API重複テストジャンル',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/books', [
            'title' => '重複登録しようとする書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000400',
            'published_date' => '2026-10-21',
            'genres' => [$genre->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_book_store_rejects_nonexistent_genre(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/books', [
            'title' => 'ジャンル不正テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000500',
            'published_date' => '2026-10-21',
            'genres' => [999999],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genres.0']);
    }
}
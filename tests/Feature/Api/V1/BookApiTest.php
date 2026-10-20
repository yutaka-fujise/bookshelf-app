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
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_book_information_by_isbn(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'authors' => ['山田太郎', '佐藤花子'],
                            'description' => 'Laravelの入門書です。',
                            'publishedDate' => '2025-04-01',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/cover.jpg',
                            ],
                            'categories' => ['プログラミング'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertOk();

        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => '山田太郎, 佐藤花子',
            'description' => 'Laravelの入門書です。',
            'published_date' => '2025-04-01',
            'image_url' => 'https://example.com/cover.jpg',
            'categories' => ['プログラミング'],
        ]);

        Http::assertSent(function ($request) {
            return str_starts_with(
                $request->url(),
                'https://www.googleapis.com/books/v1/volumes'
            ) && str_contains(
                urldecode($request->url()),
                'isbn:9784101010014'
            );
        });
    }

    public function test_returns_404_when_isbn_is_not_found(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),

            'openlibrary.org/isbn/*' => Http::response([
                'error' => 'Not Found',
            ], 404),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertNotFound();

        $response->assertJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_returns_502_when_both_book_apis_fail(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => 'Service Unavailable',
            ], 503),

            'openlibrary.org/isbn/*' => Http::response([
                'error' => 'Service Unavailable',
            ], 503),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(502);

        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    public function test_fetches_book_information_from_open_library_when_google_books_has_no_results(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),

            'openlibrary.org/isbn/*' => Http::response([
                'title' => 'Harry Potter and the sorcerer\'s stone',
                'authors' => [
                    ['key' => '/authors/OL23919A'],
                ],
                'description' => [
                    'type' => '/type/text',
                    'value' => 'A young wizard attends Hogwarts.',
                ],
                'publish_date' => '1999',
                'covers' => [14656855],
            ], 200),

            'openlibrary.org/authors/*' => Http::response([
                'name' => 'J. K. Rowling',
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9780439708180');

        $response->assertOk();

        $response->assertJson([
            'title' => 'Harry Potter and the sorcerer\'s stone',
            'author' => 'J. K. Rowling',
            'description' => 'A young wizard attends Hogwarts.',
            'published_date' => '1999',
            'image_url' => 'https://covers.openlibrary.org/b/id/14656855-M.jpg',
        ]);
    }

    public function test_fetches_from_open_library_when_google_books_api_fails(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => 'Invalid API key',
            ], 400),

            'openlibrary.org/isbn/*' => Http::response([
                'title' => '吾輩は猫である',
                'description' => '夏目漱石の小説',
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertOk();

        $response->assertJson([
            'title' => '吾輩は猫である',
            'description' => '夏目漱石の小説',
            'categories' => [],
        ]);
    }
}

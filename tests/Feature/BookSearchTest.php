<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_title_or_author(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'PHP基礎',
            'author' => 'Laravel先生',
            'isbn13' => '9784000000001',
            'published_at' => '2026-10-14',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'Python入門',
            'author' => '佐藤花子',
            'isbn13' => '9784000000002',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertSee('PHP基礎');
        $response->assertDontSee('Python入門');
    }

    public function test_search_returns_no_books_when_keyword_does_not_match(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->get('/books?keyword=Python');

        $response->assertOk();
        $response->assertDontSee('Laravel入門');
        $response->assertSee('書籍が見つかりませんでした。');
    }
}
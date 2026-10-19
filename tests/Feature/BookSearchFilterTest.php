<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_filtered_by_keyword_and_genre(): void
    {
        $user = User::factory()->create();

        $novel = Genre::create([
            'name' => '小説',
        ]);

        $technology = Genre::create([
            'name' => '技術書',
        ]);

        $books = [
            ['Laravel小説', '山田太郎', '9784000000000', $novel],
            ['Laravel技術書', '佐藤花子', '9784000000001', $technology],
            ['PHP小説', '鈴木一郎', '9784000000002', $novel],
        ];

        foreach ($books as [$title, $author, $isbn, $genre]) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => $title,
                'author' => $author,
                'isbn13' => $isbn,
                'published_at' => '2026-10-14',
            ]);

            $book->genres()->attach($genre->id);
        }

        $response = $this->get('/books?' . http_build_query([
            'keyword' => 'Laravel',
            'genre' => $novel->id,
        ]));

        $response->assertOk();
        $response->assertSee('Laravel小説');
        $response->assertDontSee('Laravel技術書');
        $response->assertDontSee('PHP小説');
    }
}
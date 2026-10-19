<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchFilterSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_filtered_and_sorted(): void
    {
        $user = User::factory()->create();

        $novel = Genre::create([
            'name' => '小説',
        ]);

        $technology = Genre::create([
            'name' => '技術書',
        ]);

        $books = [
            ['Laravel小説B', '9784000000000', $novel],
            ['Laravel技術書', '9784000000001', $technology],
            ['PHP小説', '9784000000002', $novel],
            ['Laravel小説A', '9784000000003', $novel],
        ];

        foreach ($books as [$title, $isbn, $genre]) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => $title,
                'author' => 'テスト著者',
                'isbn13' => $isbn,
                'published_at' => '2026-10-14',
            ]);

            $book->genres()->attach($genre->id);
        }

        $response = $this->get('/books?' . http_build_query([
            'keyword' => 'Laravel',
            'genre' => $novel->id,
            'sort' => 'title',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Laravel小説A',
            'Laravel小説B',
        ]);
        $response->assertDontSee('Laravel技術書');
        $response->assertDontSee('PHP小説');
    }
}
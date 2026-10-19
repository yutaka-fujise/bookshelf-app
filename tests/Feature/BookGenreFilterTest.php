<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookGenreFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();

        $novel = Genre::create([
            'name' => '小説',
        ]);

        $technology = Genre::create([
            'name' => '技術書',
        ]);

        $novelBook = Book::create([
            'user_id' => $user->id,
            'title' => 'ミステリー小説',
            'author' => '山田太郎',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $novelBook->genres()->attach($novel->id);

        $technologyBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel実践ガイド',
            'author' => '佐藤花子',
            'isbn13' => '9784000000001',
            'published_at' => '2026-10-14',
        ]);

        $technologyBook->genres()->attach($technology->id);

        $response = $this->get('/books?genre=' . $novel->id);

        $response->assertOk();
        $response->assertSee('ミステリー小説');
        $response->assertDontSee('Laravel実践ガイド');
    }
}
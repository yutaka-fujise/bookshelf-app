<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filter_and_sort_can_be_reset(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $laravelBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn13' => '9781234567890',
        ]);

        $pythonBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Python入門',
            'author' => '佐藤花子',
            'isbn13' => '9781234567891',
        ]);

        $laravelBook->genres()->attach($genre->id);

        // 検索・ジャンル・ソートを指定した状態
        $filteredResponse = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'title',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Laravel入門');
        $filteredResponse->assertDontSee('Python入門');

        // リセットリンクは検索条件なしの一覧へ戻る
        $filteredResponse->assertSee(
            'href="' . route('books.index') . '"',
            false
        );

        // リセット先では全書籍が表示される
        $resetResponse = $this->get(route('books.index'));

        $resetResponse->assertOk();
        $resetResponse->assertSee('Laravel入門');
        $resetResponse->assertSee('Python入門');
    }
}

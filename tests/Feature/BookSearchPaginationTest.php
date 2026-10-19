<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_conditions_are_kept_when_paginating(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        // 検索条件に一致する書籍を11冊作成
        for ($i = 1; $i <= 11; $i++) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => 'Laravel入門' . $i,
                'author' => '山田太郎',
                'isbn13' => '97812345678' . str_pad($i, 2, '0', STR_PAD_LEFT),
            ]);

            $book->genres()->attach($genre->id);
        }

        $conditions = [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'title',
        ];

        // 1ページ目に検索条件付きの2ページ目リンクがある
        $response = $this->get(route('books.index', $conditions));

        $response->assertOk();
        $response->assertSee('検索結果: 11件');

        $response->assertSee(
            'keyword=Laravel',
            false
        );
        $response->assertSee(
            'genre=' . $genre->id,
            false
        );
        $response->assertSee(
            'sort=title',
            false
        );
        $response->assertSee(
            'page=2',
            false
        );

        // 2ページ目でも検索条件が維持され、残り1冊が表示される
        $secondPageResponse = $this->get(route('books.index', array_merge(
            $conditions,
            ['page' => 2]
        )));

        $secondPageResponse->assertOk();
        $secondPageResponse->assertSee('Laravel入門9');
        $secondPageResponse->assertDontSee('Laravel入門1</h3>', false);
    }
}

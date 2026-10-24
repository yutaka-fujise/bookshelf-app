<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_sorted_by_newest(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::create([
            'user_id' => $user->id,
            'title' => '古い書籍',
            'author' => '山田太郎',
            'isbn13' => '9784000000000',
            'published_at' => '2020-01-01',
        ]);

        $newBook = Book::create([
            'user_id' => $user->id,
            'title' => '新しい書籍',
            'author' => '佐藤花子',
            'isbn13' => '9784000000001',
            'published_at' => '2025-01-01',
        ]);

        $oldBook->forceFill([
            'created_at' => '2026-01-01 00:00:00',
        ])->save();

        $newBook->forceFill([
            'created_at' => '2026-10-01 00:00:00',
        ])->save();

        $response = $this->get('/books?sort=newest');

        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        $user = User::factory()->create();

        $newBook = Book::create([
            'user_id' => $user->id,
            'title' => '新しい書籍',
            'author' => '佐藤花子',
            'isbn13' => '9784000000001',
            'published_at' => '2025-01-01',
        ]);

        $oldBook = Book::create([
            'user_id' => $user->id,
            'title' => '古い書籍',
            'author' => '山田太郎',
            'isbn13' => '9784000000000',
            'published_at' => '2020-01-01',
        ]);

        $newBook->forceFill([
            'created_at' => '2026-10-01 00:00:00',
        ])->save();

        $oldBook->forceFill([
            'created_at' => '2026-01-01 00:00:00',
        ])->save();

        $response = $this->get('/books?sort=oldest');

        $response->assertOk();
        $response->assertSeeInOrder([
            '古い書籍',
            '新しい書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        $user = User::factory()->create();

        $ratings = [
            ['低評価の書籍', '9784000000000', [2]],
            ['中評価の書籍', '9784000000001', [4]],
            ['高評価の書籍', '9784000000002', [5, 4]],
        ];

        foreach ($ratings as [$title, $isbn, $scores]) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => $title,
                'author' => 'テスト著者',
                'isbn13' => $isbn,
                'published_at' => '2026-10-14',
            ]);

            foreach ($scores as $score) {
                Review::create([
                    'user_id' => User::factory()->create()->id,
                    'book_id' => $book->id,
                    'rating' => $score,
                    'comment' => '評価順ソートのテスト',
                ]);
            }
        }

        $response = $this->get('/books?sort=rating');

        $response->assertOk();
        $response->assertSeeInOrder([
            '高評価の書籍',
            '中評価の書籍',
            '低評価の書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        $user = User::factory()->create();

        $books = [
            ['CCC入門', '9784000000000'],
            ['AAA入門', '9784000000001'],
            ['BBB入門', '9784000000002'],
        ];

        foreach ($books as [$title, $isbn]) {
            Book::create([
                'user_id' => $user->id,
                'title' => $title,
                'author' => 'テスト著者',
                'isbn13' => $isbn,
                'published_at' => '2026-10-14',
            ]);
        }

        $response = $this->get('/books?sort=title');

        $response->assertOk();
        $response->assertSeeInOrder([
            'AAA入門',
            'BBB入門',
            'CCC入門',
        ]);
    }
}

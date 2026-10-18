<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    private int $bookSequence = 0;

    private function createBook(string $title): Book
    {
        $this->bookSequence++;

        return Book::create([
            'title' => $title,
            'author' => 'テスト著者',
            'isbn13' => sprintf('97840000%05d', $this->bookSequence),
        ]);
    }

    private function addReview(Book $book, int $rating): void
    {
        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => 'ランキングテスト用レビュー',
        ]);
    }

    // レビュー平均評価の高い順に表示される
    public function test_books_are_ranked_by_average_rating(): void
    {
        $high = $this->createBook('高評価の書籍');
        $middle = $this->createBook('中評価の書籍');
        $low = $this->createBook('低評価の書籍');

        $this->addReview($high, 5);
        $this->addReview($high, 4);
        $this->addReview($middle, 4);
        $this->addReview($low, 2);

        $response = $this->get('/ranking');

        $response->assertOk()
            ->assertViewIs('ranking.index')
            ->assertViewHas('rankedBooks', function ($books) use ($high, $middle, $low) {
                return $books->pluck('id')->all() === [
                    $high->id,
                    $middle->id,
                    $low->id,
                ];
            });
    }

    // 上位10冊のみ表示される
    public function test_ranking_displays_only_top_ten_books(): void
    {
        for ($rating = 1; $rating <= 11; $rating++) {
            $book = $this->createBook("ランキング書籍{$rating}");
            $this->addReview($book, 5);
        }

        $response = $this->get('/ranking');

        $response->assertOk()
            ->assertViewHas('rankedBooks', function ($books) {
                return $books->count() === 10;
            });
    }

    // レビュー0件の書籍は表示されない
    public function test_books_without_reviews_are_excluded(): void
    {
        $reviewedBook = $this->createBook('レビューありの書籍');
        $unreviewedBook = $this->createBook('レビューなしの書籍');

        $this->addReview($reviewedBook, 5);

        $response = $this->get('/ranking');

        $response->assertOk()
            ->assertSee('レビューありの書籍')
            ->assertDontSee('レビューなしの書籍');
    }

    // 未ログインユーザーも閲覧できる
    public function test_guest_can_view_ranking(): void
    {
        $response = $this->get('/ranking');

        $response->assertOk()
            ->assertViewIs('ranking.index');
    }
}

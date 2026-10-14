<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビューを保存し、関連データを取得できること
     */
    public function test_review_can_be_saved_and_retrieved(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // レビューを作成
        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした！',
        ]);

        // DBに保存されていること
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした！',
        ]);

        // レビューから投稿者を取得できること
        $this->assertSame(
            $user->id,
            $review->user->id
        );

        // レビューから対象書籍を取得できること
        $this->assertSame(
            $book->id,
            $review->book->id
        );

        // ユーザーからレビューを取得できること
        $this->assertTrue(
            $user->reviews()->whereKey($review->id)->exists()
        );

        // 書籍からレビューを取得できること
        $this->assertTrue(
            $book->reviews()->whereKey($review->id)->exists()
        );
    }

    /**
     * 同じユーザーが同じ書籍にレビューを重複投稿できないこと
     */
    public function test_duplicate_review_is_rejected(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'レビュー重複確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // 1回目のレビューを登録
        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした！',
        ]);

        // レビューが1件登録されたことを確認
        $this->assertDatabaseCount('reviews', 1);

        // 2回目はDB例外が発生することを期待
        $this->expectException(QueryException::class);

        // 同じユーザーが同じ書籍に再度レビューを登録
        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'もう一度レビューします！',
        ]);
    }
}
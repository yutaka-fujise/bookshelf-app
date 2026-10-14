<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikePersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビューへのいいねを保存し、関連データを取得できること
     */
    public function test_review_like_can_be_saved_and_retrieved(): void
    {
        // レビュー投稿者を作成
        $reviewUser = User::create([
            'name' => 'レビュー投稿者',
            'email' => 'review@example.com',
            'password' => 'password123',
        ]);

        // いいねするユーザーを作成
        $likeUser = User::create([
            'name' => 'いいねユーザー',
            'email' => 'like@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'レビューいいねテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // レビューを作成
        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'おすすめの本です！',
        ]);

        // レビューにいいねする
        $like = ReviewLike::create([
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        // DBへの保存を確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        // いいねからユーザーを取得できること
        $this->assertSame(
            $likeUser->id,
            $like->user->id
        );

        // いいねからレビューを取得できること
        $this->assertSame(
            $review->id,
            $like->review->id
        );

        // レビューからいいねしたユーザーを取得できること
        $this->assertTrue(
            $review->likedUsers()->whereKey($likeUser->id)->exists()
        );

        // ユーザーからいいねしたレビューを取得できること
        $this->assertTrue(
            $likeUser->likedReviews()->whereKey($review->id)->exists()
        );
    }

    /**
     * 同じレビューへの重複いいねを防ぐこと
     */
    public function test_duplicate_review_like_is_rejected(): void
    {
        // レビュー投稿者を作成
        $reviewUser = User::create([
            'name' => 'レビュー投稿者',
            'email' => 'review@example.com',
            'password' => 'password123',
        ]);

        // いいねするユーザーを作成
        $likeUser = User::create([
            'name' => 'いいねユーザー',
            'email' => 'like@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'いいね重複確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // レビューを作成
        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '面白い本でした！',
        ]);

        // いいね用のデータを用意
        $attributes = [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ];

        // 1回目のいいねは正常に登録
        ReviewLike::create($attributes);

        // 1件登録されたことを確認
        $this->assertDatabaseCount('review_likes', 1);

        // 2回目はDB例外が発生することを期待
        $this->expectException(QueryException::class);

        // 同じレビューに再度いいねする
        ReviewLike::create($attributes);
    }
}
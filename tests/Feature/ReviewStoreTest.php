<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_review(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000007',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 5,
                'comment' => 'とても面白い本でした。',
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_guest_cannot_store_review(): void
    {
        $book = Book::create([
            'title' => 'ゲスト用テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000008',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => '未ログインでのレビュー投稿',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_review_same_book_twice(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => '重複レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000009',
            'published_at' => '2026-10-14',
        ]);

        // すでにレビューを1件投稿している状態
        $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => '最初のレビュー',
        ]);

        // 同じユーザーが再投稿
        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => '2回目のレビュー',
            ]);

        $response->assertSessionHasErrors('review');

        // レビュー件数が増えていないこと
        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseMissing('reviews', [
            'comment' => '2回目のレビュー',
        ]);
    }

    public function test_review_store_requires_mandatory_fields(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => '必須項目テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000010',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => '',
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_store_rejects_invalid_rating(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => '評価範囲テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000011',
            'published_at' => '2026-10-14',
        ]);

        foreach ([0, 6] as $rating) {
            $response = $this->actingAs($user)
                ->post(route('reviews.store', $book), [
                    'rating' => $rating,
                    'comment' => '評価範囲の確認です。',
                ]);

            $response->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_store_rejects_comment_over_1000_characters(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'レビュー文字数テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000012',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }
}
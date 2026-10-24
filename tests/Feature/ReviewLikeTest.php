<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_toggle_review_like(): void
    {
        $reviewer = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'いいねテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000013',
            'published_at' => '2026-10-14',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'いいねテスト用レビュー',
        ]);

        // 1回目：いいね登録
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review));

        $response->assertRedirect();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 2回目：いいね解除
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review));

        $response->assertRedirect();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_cannot_toggle_review_like(): void
    {
        $reviewer = User::factory()->create();

        $book = Book::create([
            'title' => 'ゲストいいねテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000014',
            'published_at' => '2026-10-14',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'ゲストいいねテスト',
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }
}
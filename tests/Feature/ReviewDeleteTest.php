<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人はレビューを削除できる
    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => 'レビュー削除テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000050',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 5,
            'comment' => '削除対象のレビュー',
        ]);

        $response = $this->actingAs($owner)
            ->delete("/reviews/{$review->id}");

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    // 他人のレビューは削除できない
    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'title' => '他人のレビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000051',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '削除禁止のレビュー',
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/reviews/{$review->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '削除禁止のレビュー',
        ]);
    }

    // 未ログインユーザーはレビューを削除できない
    public function test_guest_cannot_delete_review(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '未ログイン用レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000052',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 3,
            'comment' => 'ログイン必須のレビュー',
        ]);

        $response = $this->delete("/reviews/{$review->id}");

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => 'ログイン必須のレビュー',
        ]);
    }
}

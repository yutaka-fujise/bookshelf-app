<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人はレビューを更新できる
    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'レビュー更新テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000030',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のコメント',
        ]);

        $response = $this->actingAs($user)
            ->put("/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => '更新後のコメント',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);
    }

    // 他人のレビューは更新できない
    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'title' => '他人のレビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000031',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '変更禁止のコメント',
        ]);

        $response = $this->actingAs($otherUser)
            ->put("/reviews/{$review->id}", [
                'rating' => 1,
                'comment' => '不正な変更',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '変更禁止のコメント',
        ]);
    }

    // 未ログインではレビューを更新できない
    public function test_guest_cannot_update_review(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => 'ログイン必須のレビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000032',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);

        $response = $this->put("/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => '不正な変更',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '元のコメント',
        ]);
    }

    // 評価は1〜5の整数のみ
    public function test_review_update_rejects_invalid_rating(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '評価範囲テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000033',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        foreach ([0, 6] as $rating) {
            $response = $this->actingAs($owner)
                ->put("/reviews/{$review->id}", [
                    'rating' => $rating,
                    'comment' => '更新テスト',
                ]);

            $response->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    // 必須項目が未入力なら更新できない
    public function test_review_update_requires_mandatory_fields(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '必須項目テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000034',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);

        $response = $this->actingAs($owner)
            ->put("/reviews/{$review->id}", [
                'rating' => '',
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);
    }

    // コメントは1000文字以内
    public function test_review_update_rejects_long_comment(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '文字数制限テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000035',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);

        $response = $this->actingAs($owner)
            ->put("/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);
    }
}

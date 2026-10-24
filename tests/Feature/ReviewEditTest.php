<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEditTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人は編集画面を開ける
    public function test_owner_can_view_review_edit_form(): void
    {
        $owner = User::factory()->create();
        $book = Book::create([
            'title' => 'レビュー編集テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000040',
        ]);
        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '編集前のレビューです。',
        ]);

        $response = $this->actingAs($owner)
            ->get("/reviews/{$review->id}/edit");

        $response->assertOk();
        $response->assertViewIs('reviews.edit');

        // 書籍名・既存コメント・更新先を確認
        $response->assertSee('レビュー編集テスト書籍');
        $response->assertSee('編集前のレビューです。');
        $response->assertSee(route('reviews.update', $review), false);

        // 既存の評価（4）が選択されていることを確認
        $this->assertMatchesRegularExpression(
            '/<input\b(?=[^>]*name="rating")(?=[^>]*value="4")(?=[^>]*\bchecked\b)[^>]*>/s',
            $response->getContent()
        );
    }

    // 他人のレビュー編集画面は開けない
    public function test_other_user_cannot_view_review_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'title' => '他人のレビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000041',
        ]);
        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 5,
            'comment' => '他人のレビュー',
        ]);

        $response = $this->actingAs($otherUser)
            ->get("/reviews/{$review->id}/edit");

        $response->assertForbidden();
    }

    // 未ログインではレビュー編集画面を開けない
    public function test_guest_cannot_view_review_edit_form(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '未ログイン用レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000042',
        ]);
        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 3,
            'comment' => 'ログインが必要なレビュー',
        ]);

        $response = $this->get("/reviews/{$review->id}/edit");

        $response->assertRedirect(route('login'));
    }
}

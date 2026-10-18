<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewActionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人には編集・削除ボタンが表示される
    public function test_owner_can_see_review_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => 'レビューボタン確認用の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000060',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 5,
            'comment' => '本人のレビュー',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(route('reviews.edit', $review), false);
        $response->assertSee(
            'action="' . route('reviews.destroy', $review) . '"',
            false
        );
    }

    // 他人にはレビューの編集・削除ボタンが表示されない
    public function test_other_user_cannot_see_review_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'title' => '他人のレビューボタン確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000061',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 4,
            'comment' => '他人のレビュー',
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee(route('reviews.edit', $review), false);
        $response->assertDontSee(
            'action="' . route('reviews.destroy', $review) . '"',
            false
        );
    }

    // 未ログインユーザーにも編集・削除ボタンが表示されない
    public function test_guest_cannot_see_review_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => 'ゲスト向けレビューボタン確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000062',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $owner->id,
            'rating' => 3,
            'comment' => '公開レビュー',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('公開レビュー');
        $response->assertDontSee(route('reviews.edit', $review), false);
        $response->assertDontSee(
            'action="' . route('reviews.destroy', $review) . '"',
            false
        );
    }
}

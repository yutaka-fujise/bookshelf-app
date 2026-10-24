<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookEditTest extends TestCase
{
    use RefreshDatabase;

    // 作成者本人は編集画面を開き、既存情報を確認できる
    public function test_owner_can_view_book_edit_form(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '編集前の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $genre = Genre::create([
            'name' => '小説',
        ]);
        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)
            ->get("/books/{$book->id}/edit");

        $response->assertOk();
        $response->assertViewIs('books.edit');
        $response->assertSee('編集前の書籍');
        $response->assertSee('9784000000000');
        $response->assertSee('2026-10-14');
        $response->assertSee('小説');

        // 登録済みジャンルにチェックが付いている
        $response->assertSee(
            'value="' . $genre->id . '"',
            false
        );
        $response->assertSee('checked', false);
    }

    // 他人の書籍の編集画面は開けない
    public function test_other_user_cannot_view_book_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '他人の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000001',
        ]);

        $response = $this->actingAs($otherUser)
            ->get("/books/{$book->id}/edit");

        $response->assertForbidden();
    }

    // 未ログインでは編集画面を開けない
    public function test_guest_cannot_view_book_edit_form(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'ログイン必須の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000002',
        ]);

        $response = $this->get("/books/{$book->id}/edit");

        $response->assertRedirect(route('login'));
    }
}

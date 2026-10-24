<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    // 作成者本人は書籍を削除できる
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '削除対象の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $response = $this->actingAs($owner)
            ->delete("/books/{$book->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 他人の書籍は削除できない
    public function test_other_user_cannot_delete_book(): void
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
            ->delete("/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '他人の書籍',
        ]);
    }

    // 未ログインユーザーは削除できない
    public function test_guest_cannot_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'ログイン必須の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000002',
        ]);

        $response = $this->delete("/books/{$book->id}");

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'ログイン必須の書籍',
        ]);
    }
}

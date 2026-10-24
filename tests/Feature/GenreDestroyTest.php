<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDestroyTest extends TestCase
{
    use RefreshDatabase;

    // 書籍と紐付いていないジャンルは削除できる
    public function test_authenticated_user_can_delete_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'ミステリー']);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    // 書籍と紐付いているジャンルは削除できない
    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $book = Book::create([
            'title' => 'テスト小説',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $genre->books()->attach($book);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            '書籍が紐付いているジャンルは削除できません。'
        );

        // ジャンルが削除されていないこと
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);

        // 書籍も削除されていないこと
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト小説',
        ]);

        // 書籍とジャンルの紐付けも維持されていること
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 存在しないジャンルは404になる
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->delete('/genres/999999');

        $response->assertNotFound();
    }

    // 未ログインではジャンルを削除できない
    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::create(['name' => '歴史']);

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}

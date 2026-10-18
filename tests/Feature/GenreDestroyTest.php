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

    // ログインユーザーはジャンルを削除できる
    public function test_authenticated_user_can_delete_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'ミステリー']);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    // ジャンルを削除しても紐付く書籍は削除されない
    public function test_deleting_genre_does_not_delete_books(): void
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

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト小説',
        ]);

        $this->assertDatabaseMissing('book_genre', [
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

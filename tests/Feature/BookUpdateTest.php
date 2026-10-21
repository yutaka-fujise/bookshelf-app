<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    // 作成者本人は書籍を更新できる
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-14',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-10-15',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn13' => '9784000000000',
            'published_at' => '2026-10-15',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 他のユーザーは書籍を更新できない
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '変更禁止の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000001',
        ]);

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title' => '不正な変更',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更禁止の書籍',
        ]);
    }

    // 未ログインユーザーは書籍を更新できない
    public function test_guest_cannot_update_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'ログイン必須の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000002',
        ]);

        $response = $this->put("/books/{$book->id}", [
            'title' => '不正な変更',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'ログイン必須の書籍',
        ]);
    }

    // 変更がなければ更新せず、専用メッセージを表示する
    public function test_updating_book_without_changes_shows_message(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '変更なしテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000001900',
            'published_at' => '2026-10-21',
        ]);

        $genre = Genre::create([
            'name' => '変更なしテストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        // 更新前の日時を保存しておく
        $originalUpdatedAt = $book->fresh()->updated_at;

        $response = $this->actingAs($user)
            ->put("/books/{$book->id}", [
                'title' => '変更なしテスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000001900',
                'published_date' => '2026-10-21',
                'genres' => [$genre->id],
            ]);

        $response->assertRedirect(route('books.show', $book))
            ->assertSessionHas('info', '変更内容がありません。');

        // 詳細画面にもメッセージが表示されることを確認
        $this->actingAs($user)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('変更内容がありません。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更なしテスト書籍',
            'author' => 'テスト著者',
        ]);

        // 変更がない場合は更新日時も変わらない
        $this->assertEquals(
            $originalUpdatedAt,
            $book->fresh()->updated_at
        );
    }

    // ジャンルだけ変更した場合も正常に更新できる
    public function test_owner_can_update_only_book_genre(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'ジャンル変更テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000002000',
            'published_at' => '2026-10-21',
        ]);

        $oldGenre = Genre::create([
            'name' => '変更前ジャンル',
        ]);

        $newGenre = Genre::create([
            'name' => '変更後ジャンル',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->actingAs($user)
            ->put("/books/{$book->id}", [
                'title' => 'ジャンル変更テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000002000',
                'published_date' => '2026-10-21',
                'genres' => [$newGenre->id],
            ]);

        $response->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を更新しました。');

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }
}

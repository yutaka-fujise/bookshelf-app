<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍をデータベースに保存できること
     */
    public function test_book_can_be_saved_to_database(): void
    {
        $book = Book::create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
            'published_at' => '2020-01-01',
            'description' => 'FeatureTestで登録する書籍',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト書籍',
            'isbn13' => '9781234567897',
        ]);
    }

    /**
     * 同じISBN-13の書籍を重複登録できないこと
     */
    public function test_book_isbn13_must_be_unique(): void
    {
        // 1冊目を正常に登録
        Book::create([
            'title' => '書籍A',
            'author' => '著者A',
            'isbn13' => '9781234567897',
            'published_at' => '2020-01-01',
        ]);

        // 2冊目の登録でDB例外が発生することを期待
        $this->expectException(QueryException::class);

        // 同じISBN-13で登録を試みる
        Book::create([
            'title' => '書籍B',
            'author' => '著者B',
            'isbn13' => '9781234567897',
            'published_at' => '2021-01-01',
        ]);
    }

    /**
     * 任意項目を省略して書籍を保存できること
     */
    public function test_book_can_be_saved_without_optional_fields(): void
    {
        $book = Book::create([
            'title' => '任意項目なしの書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '任意項目なしの書籍',
            'user_id' => null,
            'published_at' => null,
            'description' => null,
            'image_url' => null,
        ]);
    }

    /**
     * 登録ユーザーを削除しても書籍は残ること
     */
    public function test_book_remains_after_user_is_deleted(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // ユーザーが登録した書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除確認用の書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // ユーザーを削除
        $user->delete();

        // 書籍は残り、user_idだけnullになることを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '削除確認用の書籍',
            'user_id' => null,
        ]);
    }

    /**
     * 書籍にジャンルを紐付けて取得できること
     */
    public function test_book_can_have_genres(): void
    {
        // 書籍を作成
        $book = Book::create([
            'title' => 'ジャンル確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // ジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // 書籍とジャンルを紐付ける
        $book->genres()->attach($genre->id);

        // 中間テーブルへの登録を確認
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        // 書籍からジャンルを取得できることを確認
        $this->assertSame(
            ['小説'],
            $book->fresh()->genres->pluck('name')->all()
        );
    }

    /**
     * 同じ書籍に同じジャンルを重複して紐付けできないこと
     */
    public function test_duplicate_book_genre_is_rejected(): void
    {
        // 書籍を作成
        $book = Book::create([
            'title' => 'ジャンル重複確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // ジャンルを作成
        $genre = Genre::create([
            'name' => '小説',
        ]);

        // 1回目のジャンル紐付け
        $book->genres()->attach($genre->id);

        // 中間テーブルに1件登録されたことを確認
        $this->assertDatabaseCount('book_genre', 1);

        // 2回目はDB例外が発生することを期待
        $this->expectException(QueryException::class);

        // 同じジャンルをもう一度紐付け
        $book->genres()->attach($genre->id);
    }
}
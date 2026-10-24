<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritePersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザーが書籍をお気に入り登録できること
     */
    public function test_user_can_favorite_book(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'お気に入りテスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // お気に入り登録
        $user->favoriteBooks()->attach($book->id);

        // 中間テーブルに保存されたことを確認
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ユーザーからお気に入り書籍を取得できること
        $this->assertTrue(
            $user->fresh()->favoriteBooks->contains($book->id)
        );

        // 書籍からお気に入り登録者を取得できること
        $this->assertTrue(
            $book->fresh()->favoritedByUsers->contains($user->id)
        );
    }

    /**
     * 同じ書籍を重複してお気に入り登録できないこと
     */
    public function test_duplicate_favorite_is_rejected(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => 'お気に入り重複テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // 1回目のお気に入り登録
        $user->favoriteBooks()->attach($book->id);

        // 1件登録されたことを確認
        $this->assertDatabaseCount('favorites', 1);

        // 2回目はDB例外が発生することを期待
        $this->expectException(QueryException::class);

        // 同じ書籍を再度登録
        $user->favoriteBooks()->attach($book->id);
    }
}
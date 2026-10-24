<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーはお気に入り一覧を表示できる
    public function test_authenticated_user_can_view_favorite_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/favorites');

        $response->assertOk();
        $response->assertViewIs('favorites.index');
    }

    // 自分がお気に入り登録した書籍だけ表示される
    public function test_favorite_index_displays_only_own_favorite_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $favoriteBook = Book::create([
            'title' => '自分のお気に入り本',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $otherBook = Book::create([
            'title' => '他人のお気に入り本',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $user->favoriteBooks()->attach($favoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherBook->id);

        $response = $this->actingAs($user)
            ->get('/favorites');

        $response->assertOk();
        $response->assertSee('自分のお気に入り本');
        $response->assertDontSee('他人のお気に入り本');
    }

    // お気に入りが0件の場合は案内を表示する
    public function test_favorite_index_displays_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/favorites');

        $response->assertOk();
        $response->assertSee('お気に入りに登録された書籍はありません。');
    }

    // 未ログインユーザーはお気に入り一覧を表示できない
    public function test_guest_cannot_view_favorite_index(): void
    {
        $response = $this->get('/favorites');

        $response->assertRedirect(route('login'));
    }
}

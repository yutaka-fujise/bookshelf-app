<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーはジャンル一覧を表示できる
    public function test_authenticated_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/genres');

        $response->assertOk();
        $response->assertViewIs('genres.index');
    }

    // ジャンル名と紐付く書籍数を表示できる
    public function test_genre_index_displays_book_counts(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book1 = Book::create([
            'title' => 'テスト小説A',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $book2 = Book::create([
            'title' => 'テスト小説B',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $genre->books()->attach([
            $book1->id,
            $book2->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/genres');

        $response->assertOk();
        $response->assertSee('小説');
        $response->assertSee('2冊');
    }

    // ジャンルがない場合は案内を表示する
    public function test_genre_index_shows_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/genres');

        $response->assertOk();
        $response->assertSee('ジャンルが登録されていません。');
    }

    // 未ログインユーザーはジャンル管理画面にアクセスできない
    public function test_guest_cannot_view_genre_index(): void
    {
        $response = $this->get('/genres');

        $response->assertRedirect(route('login'));
    }
}

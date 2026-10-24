<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーはジャンル詳細を表示できる
    public function test_authenticated_user_can_view_genre_show(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'ミステリー']);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre');
        $response->assertViewHas('books');
        $response->assertSee('ミステリー');
    }

    // そのジャンルに紐付く書籍だけが表示される
    public function test_genre_show_displays_related_books_only(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);
        $otherGenre = Genre::create(['name' => '技術書']);

        $relatedBook = Book::create([
            'title' => '小説の本',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $otherBook = Book::create([
            'title' => '技術書の本',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $relatedBook->genres()->attach($genre);
        $otherBook->genres()->attach($otherGenre);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('小説の本');
        $response->assertDontSee('技術書の本');
    }

    // 書籍が存在しない場合は案内を表示する
    public function test_genre_show_displays_empty_message(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'エッセイ']);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    // 存在しないジャンルは404になる
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/genres/999999');

        $response->assertNotFound();
    }

    // 未ログインではジャンル詳細を表示できない
    public function test_guest_cannot_view_genre_show(): void
    {
        $genre = Genre::create(['name' => '歴史']);

        $response = $this->get(route('genres.show', $genre));

        $response->assertRedirect(route('login'));
    }
}
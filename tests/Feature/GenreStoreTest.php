<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreStoreTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーはジャンルを登録できる
    public function test_authenticated_user_can_store_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/genres', [
                'name' => 'ミステリー',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    // ジャンル名は必須
    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/genres', [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    // 同じジャンル名は重複登録できない
    public function test_duplicate_genre_name_is_rejected(): void
    {
        $user = User::factory()->create();

        Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->post('/genres', [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    // ジャンル名は50文字以内
    public function test_genre_name_cannot_exceed_50_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/genres', [
                'name' => str_repeat('あ', 51),
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    // 未ログインではジャンルを登録できない
    public function test_guest_cannot_store_genre(): void
    {
        $response = $this->post('/genres', [
            'name' => '未ログインの登録',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 0);
    }

    // ログインユーザーはジャンル登録画面を表示できる
    public function test_authenticated_user_can_view_genre_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/genres/create');

        $response->assertOk();
        $response->assertViewIs('genres.create');
    }

    // 未ログインではジャンル登録画面を表示できない
    public function test_guest_cannot_view_genre_create(): void
    {
        $response = $this->get('/genres/create');

        $response->assertRedirect(route('login'));
    }
}

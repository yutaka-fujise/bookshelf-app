<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーはジャンル編集画面を表示できる
    public function test_authenticated_user_can_view_genre_edit(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.edit');
        $response->assertSee('小説');
    }

    // ログインユーザーはジャンル名を更新できる
    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '文学',
            ]);

        $response->assertRedirect(route('genres.show', $genre));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '文学',
        ]);
    }

    // ジャンル名は必須
    public function test_genre_name_is_required_on_update(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    // ほかのジャンルと同じ名前には更新できない
    public function test_duplicate_genre_name_is_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);
        Genre::create(['name' => '技術書']);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '技術書',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    // 同じジャンル名の場合は更新せず、メッセージを表示する
    public function test_genre_can_keep_its_current_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $originalUpdatedAt = $genre->updated_at;

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '小説',
            ]);

        $response->assertRedirect(route('genres.show', $genre));
        $response->assertSessionHas('info', '変更内容がありません。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);

        $this->assertEquals(
            $originalUpdatedAt,
            $genre->fresh()->updated_at
        );
    }

    // 50文字を超えるジャンル名は拒否する
    public function test_genre_name_cannot_exceed_50_characters_on_update(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => str_repeat('あ', 51),
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    // 存在しないジャンルの編集画面は404
    public function test_nonexistent_genre_edit_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/genres/999999/edit');

        $response->assertNotFound();
    }

    // 未ログインでは編集画面を表示できない
    public function test_guest_cannot_view_genre_edit(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    // 未ログインではジャンルを更新できない
    public function test_guest_cannot_update_genre(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $response = $this->put(route('genres.update', $genre), [
            'name' => '文学',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }
}

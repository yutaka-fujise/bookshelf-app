<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCreateTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーは読書計画作成画面を表示できる
    public function test_authenticated_user_can_view_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.create');
        $response->assertSee('読書計画作成');
    }

    // 未ログインユーザーは作成画面を表示できない
    public function test_guest_cannot_view_create(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertRedirect(route('login'));
    }
}

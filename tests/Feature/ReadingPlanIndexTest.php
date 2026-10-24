<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(
        User $user,
        Book $book,
        string $status,
        string $targetDate = '2026-11-30'
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
        ]);
    }

    // ログインユーザーは読書計画一覧を表示できる
    public function test_authenticated_user_can_view_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');
        $response->assertSee('読書計画');
    }

    // 自分の読書計画だけ表示される
    public function test_index_displays_only_own_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = Book::create([
            'title' => '自分の読書計画の本',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $book2 = Book::create([
            'title' => '他人の読書計画の本',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $this->createPlan($user, $book1, 'in_progress');
        $this->createPlan($otherUser, $book2, 'completed');

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertSee('自分の読書計画の本');
        $response->assertDontSee('他人の読書計画の本');
    }

    // 読書計画が0件なら案内を表示する
    public function test_index_displays_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertSee('該当する読書計画はありません。');
    }

    // 状態を指定すると、その状態の計画だけ表示される
    public function test_index_filters_plans_by_status(): void
    {
        $user = User::factory()->create();

        $activeBook = Book::create([
            'title' => '進行中の本',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $completedBook = Book::create([
            'title' => '読了済みの本',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $this->createPlan($user, $activeBook, 'in_progress');
        $this->createPlan($user, $completedBook, 'completed');

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'in_progress',
            ]));

        $response->assertOk();
        $response->assertSee('進行中の本');
        $response->assertDontSee('読了済みの本');
    }

    // 期限切れの計画も絞り込める
    public function test_index_filters_expired_plans(): void
    {
        $user = User::factory()->create();

        $expiredBook = Book::create([
            'title' => '期限切れの本',
            'author' => '著者A',
            'isbn13' => '9784000000070',
        ]);

        $activeBook = Book::create([
            'title' => '進行中の本',
            'author' => '著者B',
            'isbn13' => '9784000000071',
        ]);

        $this->createPlan($user, $expiredBook, 'expired');
        $this->createPlan($user, $activeBook, 'in_progress');

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'expired',
            ]));

        $response->assertOk();
        $response->assertSee('期限切れの本');
        $response->assertDontSee('進行中の本');
    }

    // 未ログインユーザーは一覧を表示できない
    public function test_guest_cannot_view_index(): void
    {
        $response = $this->get('/reading-plans');

        $response->assertRedirect(route('login'));
    }
}

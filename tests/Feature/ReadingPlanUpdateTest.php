<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(
        User $user,
        string $status = 'in_progress'
    ): ReadingPlan {
        $book = Book::create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => $status,
            'completed_at' => $status === 'completed'
                ? '2026-10-01 12:00:00'
                : null,
        ]);
    }

    // 所有者は進行中の計画の編集画面を表示できる
    public function test_owner_can_view_edit(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');
    }

    // 所有者は目標日を更新できる
    public function test_owner_can_update_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-12-20',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2026-12-20',
            'status' => 'in_progress',
        ]);
    }

    // 期限切れの計画は編集すると進行中に戻る
    public function test_expired_plan_returns_to_in_progress_after_update(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'expired');

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-12-20',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'in_progress',
            'target_date' => '2026-12-20',
        ]);
    }

    // 読了済みの計画は編集できない
    public function test_completed_plan_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'completed');

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-12-20',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'completed',
            'target_date' => '2026-11-30',
        ]);
    }

    // 他人の計画は編集できない
    public function test_other_user_cannot_update_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner);

        $response = $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-12-20',
            ]);

        $response->assertForbidden();
    }

    // 書籍ID・所有者・ステータスを変更できない
    public function test_cannot_change_book_owner_or_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($user);

        $otherBook = Book::create([
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn13' => '9784000000071',
        ]);

        $originalBookId = $plan->book_id;

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-12-20',
                'book_id' => $otherBook->id,
                'user_id' => $otherUser->id,
                'status' => 'completed',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'book_id' => $originalBookId,
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);
    }

    // 目標日は必須
    public function test_target_date_is_required_on_update(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors('target_date');
    }

    // 存在しない計画は404
    public function test_nonexistent_plan_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reading-plans/999999/edit');

        $response->assertNotFound();
    }

    // 未ログインでは編集できない
    public function test_guest_cannot_update_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this->put(route('reading-plans.update', $plan), [
            'target_date' => '2026-12-20',
        ]);

        $response->assertRedirect(route('login'));
    }

    // 同じ書籍に進行中計画がある場合、
    // 期限切れ計画を進行中に戻せない
    public function test_expired_plan_cannot_be_reactivated_when_active_plan_exists(): void
    {
        $user = User::factory()->create();
        $expiredPlan = $this->createPlan($user, 'expired');

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $expiredPlan->book_id,
            'target_date' => '2026-12-10',
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $expiredPlan), [
                'target_date' => '2026-12-20',
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'status' => 'expired',
            'target_date' => '2026-11-30',
        ]);
    }
}

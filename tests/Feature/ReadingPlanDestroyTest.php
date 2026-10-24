<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(User $user, string $status): ReadingPlan
    {
        $book = Book::create([
            'title' => '削除テスト用書籍',
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

    // 進行中の読書計画を削除できる
    public function test_owner_can_delete_in_progress_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'in_progress');

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    // 期限切れの読書計画を削除できる
    public function test_owner_can_delete_expired_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'expired');

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    // 読了済みの読書計画も削除できる
    public function test_owner_can_delete_completed_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'completed');

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    // 他人の読書計画は削除できない
    public function test_other_user_cannot_delete_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner, 'in_progress');

        $response = $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertForbidden();
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    // 存在しない読書計画は404
    public function test_nonexistent_plan_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->delete('/reading-plans/999999');

        $response->assertNotFound();
    }

    // 未ログインユーザーは削除できない
    public function test_guest_cannot_delete_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'in_progress');

        $response = $this->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCompleteTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(User $user, string $status): ReadingPlan
    {
        $book = Book::create([
            'title' => '読了テスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => $status,
            'completed_at' => null,
        ]);
    }

    // 進行中の計画を読了済みにできる
    public function test_owner_can_complete_in_progress_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'in_progress');

        $response = $this->actingAs($user)
            ->patch(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame('completed', $plan->status);
        $this->assertNotNull($plan->completed_at);
    }

    // 期限切れの計画も読了済みにできる
    public function test_owner_can_complete_expired_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'expired');

        $response = $this->actingAs($user)
            ->patch(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame('completed', $plan->status);
        $this->assertNotNull($plan->completed_at);
    }

    // 読了済みの計画は再度読了処理を実行できない
    public function test_completed_plan_cannot_be_completed_again(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'completed');

        $response = $this->actingAs($user)
            ->patch(route('reading-plans.complete', $plan));

        $response->assertForbidden();
    }

    // 他人の計画を読了済みにできない
    public function test_other_user_cannot_complete_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner, 'in_progress');

        $response = $this->actingAs($otherUser)
            ->patch(route('reading-plans.complete', $plan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'in_progress',
        ]);
    }

    // 存在しない計画は404
    public function test_nonexistent_plan_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/reading-plans/999999/complete');

        $response->assertNotFound();
    }

    // 未ログインユーザーは読了処理を実行できない
    public function test_guest_cannot_complete_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, 'in_progress');

        $response = $this->patch(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'in_progress',
        ]);
    }
}

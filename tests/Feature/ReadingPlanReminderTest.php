<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createPlan(
        string $targetDate,
        string $status = 'in_progress'
    ): ReadingPlan {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => '通知テスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
            'completed_at' => null,
        ]);
    }

    // 期日の3日前に通知を生成する
    public function test_creates_reminder_three_days_before(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $plan = $this->createPlan('2026-10-22');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $plan->user_id,
            'reading_plan_id' => $plan->id,
            'target_date' => '2026-10-22',
            'reminder_type' => 'before_3_days',
            'read_at' => null,
        ]);
    }

    // 期日当日に通知を生成する
    public function test_creates_reminder_on_target_date(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $plan = $this->createPlan('2026-10-19');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'reading_plan_id' => $plan->id,
            'target_date' => '2026-10-19',
            'reminder_type' => 'on_target_date',
        ]);
    }

    // 期日の3日後に通知を生成する
    public function test_creates_reminder_three_days_after(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $plan = $this->createPlan('2026-10-16', 'expired');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'reading_plan_id' => $plan->id,
            'target_date' => '2026-10-16',
            'reminder_type' => 'after_3_days',
        ]);
    }

    // 同じ通知を二重生成しない
    public function test_does_not_create_duplicate_reminders(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $plan = $this->createPlan('2026-10-22');

        $this->artisan('reading-plans:remind')->assertExitCode(0);
        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);
    }

    // 読了済みの計画には通知しない
    public function test_does_not_remind_completed_plans(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $this->createPlan('2026-10-22', 'completed');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);
    }

    // 通知対象日でなければ通知しない
    public function test_does_not_remind_on_other_dates(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $this->createPlan('2026-10-25');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);
    }

    // 期限切れでも3日前の通知を生成していなければ通知できる
    public function test_expired_plan_can_receive_before_reminder(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $this->createPlan('2026-10-22', 'expired');

        $this->artisan('reading-plans:remind')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'reminder_type' => 'before_3_days',
        ]);
    }
}

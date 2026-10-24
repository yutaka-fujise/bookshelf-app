<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanExpireTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createPlan(
        User $user,
        Book $book,
        string $targetDate,
        string $status = 'in_progress'
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
            'completed_at' => null,
        ]);
    }

    // 期日を過ぎた進行中の計画は期限切れになる
    public function test_past_due_plan_becomes_expired(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $user = User::factory()->create();

        $book = Book::create([
            'title' => '期限切れテスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = $this->createPlan($user, $book, '2026-10-18');

        $this->artisan('reading-plans:expire')->assertExitCode(0);

        $this->assertSame('expired', $plan->fresh()->status);
    }

    // 期日当日は進行中のまま
    public function test_plan_due_today_stays_in_progress(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $user = User::factory()->create();

        $book = Book::create([
            'title' => '期限切れテスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = $this->createPlan($user, $book, '2026-10-19');

        $this->artisan('reading-plans:expire')->assertExitCode(0);

        $this->assertSame('in_progress', $plan->fresh()->status);
    }

    // 未来の期日は進行中のまま
    public function test_future_plan_stays_in_progress(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $user = User::factory()->create();

        $book = Book::create([
            'title' => '期限切れテスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = $this->createPlan($user, $book, '2026-10-20');

        $this->artisan('reading-plans:expire')->assertExitCode(0);

        $this->assertSame('in_progress', $plan->fresh()->status);
    }

    // 読了済みの計画は期限切れに変更しない
    public function test_completed_plan_stays_completed(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $user = User::factory()->create();

        $book = Book::create([
            'title' => '期限切れテスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = $this->createPlan($user, $book, '2026-10-18', 'completed');

        $this->artisan('reading-plans:expire')->assertExitCode(0);

        $this->assertSame('completed', $plan->fresh()->status);
    }

    // すでに期限切れの計画はそのまま
    public function test_expired_plan_stays_expired(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $user = User::factory()->create();

        $book = Book::create([
            'title' => '期限切れテスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = $this->createPlan($user, $book, '2026-10-18', 'expired');

        $this->artisan('reading-plans:expire')->assertExitCode(0);

        $this->assertSame('expired', $plan->fresh()->status);
    }
}
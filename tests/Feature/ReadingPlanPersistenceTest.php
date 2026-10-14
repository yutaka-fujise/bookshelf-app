<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画を保存し、関連データを取得できること
     */
    public function test_reading_plan_can_be_saved_and_retrieved(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => '読書計画テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // 読書計画を作成
        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        // DBに保存されていること
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        // 読書計画からユーザーを取得
        $this->assertSame(
            $user->id,
            $plan->user->id
        );

        // 読書計画から書籍を取得
        $this->assertSame(
            $book->id,
            $plan->book->id
        );

        // ユーザーから読書計画を取得
        $this->assertTrue(
            $user->readingPlans()
                ->whereKey($plan->id)
                ->exists()
        );

        // 書籍から読書計画を取得
        $this->assertTrue(
            $book->readingPlans()
                ->whereKey($plan->id)
                ->exists()
        );

        // 目標日が日付として取得できること
        $this->assertSame(
            '2026-11-30',
            $plan->fresh()->target_date->format('Y-m-d')
        );
    }

    /**
     * 同じユーザー・書籍で複数の読書計画を保存できること
     */
    public function test_user_can_have_multiple_reading_plans_for_same_book(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => '再読確認用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9781234567897',
        ]);

        // 1回目：読了済みの読書計画
        $completedPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-09-30',
            'status' => 'completed',
            'completed_at' => '2026-09-28 12:00:00',
        ]);

        // 2回目：新しく開始した読書計画
        $newPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        // 読書計画が2件登録されたこと
        $this->assertDatabaseCount('reading_plans', 2);

        // 読了済みの計画が残っていること
        $this->assertDatabaseHas('reading_plans', [
            'id' => $completedPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'completed',
        ]);

        // 新しい読書計画も登録されていること
        $this->assertDatabaseHas('reading_plans', [
            'id' => $newPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'in_progress',
        ]);
    }
}
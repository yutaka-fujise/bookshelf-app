<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Notification;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class NotificationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 通知を保存し、関連データを取得できること
     */
    public function test_notification_can_be_saved_and_retrieved(): void
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 書籍を作成
        $book = Book::create([
            'title' => '通知テスト書籍',
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

        // 通知を作成
        $notification = Notification::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'target_date' => '2026-11-30',
            'reminder_type' => 'before_3_days',
            'message' => '読書計画の目標日まであと3日です。',
            'read_at' => null,
        ]);

        // DBに保存されたことを確認
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'reminder_type' => 'before_3_days',
            'read_at' => null,
        ]);

        // 通知からユーザーを取得
        $this->assertSame(
            $user->id,
            $notification->user->id
        );

        // 通知から読書計画を取得
        $this->assertSame(
            $plan->id,
            $notification->readingPlan->id
        );

        // ユーザーから通知を取得
        $this->assertTrue(
            $user->readingPlanNotifications()
                ->whereKey($notification->id)
                ->exists()
        );

        // 読書計画から通知を取得
        $this->assertTrue(
            $plan->notifications()
                ->whereKey($notification->id)
                ->exists()
        );

        // 目標日が日付として取得できること
        $this->assertSame(
            '2026-11-30',
            $notification->fresh()->target_date->format('Y-m-d')
        );

        // 未読状態で登録されていること
        $this->assertNull(
            $notification->fresh()->read_at
        );
    }
    
/**
 * 同じ読書計画・目標日・通知種別の重複登録を防ぐこと
 */
public function test_duplicate_notification_is_rejected(): void
{
    // ユーザーを作成
    $user = User::create([
        'name' => 'テストユーザー',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // 書籍を作成
    $book = Book::create([
        'title' => '通知重複テスト書籍',
        'author' => 'テスト著者',
        'isbn13' => '9781234567897',
    ]);

    // 読書計画を作成
    $plan = ReadingPlan::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'target_date' => '2026-11-30',
        'status' => 'in_progress',
    ]);

    // 通知データを用意
    $attributes = [
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'target_date' => '2026-11-30',
        'reminder_type' => 'before_3_days',
        'message' => '目標日まであと3日です。',
    ];

    // 1件目は正常に登録
    Notification::create($attributes);

    // 通知が1件保存されたことを確認
    $this->assertDatabaseCount('notifications', 1);

    // 2件目はDB例外が発生することを期待
    $this->expectException(QueryException::class);

    // 同じ内容で重複登録を試みる
    Notification::create($attributes);
}

/**
 * 通知を未読から既読に変更できること
 */
public function test_notification_can_be_marked_as_read(): void
{
    // ユーザーを作成
    $user = User::create([
        'name' => 'テストユーザー',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // 書籍を作成
    $book = Book::create([
        'title' => '既読確認用書籍',
        'author' => 'テスト著者',
        'isbn13' => '9781234567897',
    ]);

    // 読書計画を作成
    $plan = ReadingPlan::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'target_date' => '2026-11-30',
        'status' => 'in_progress',
    ]);

    // 未読通知を作成
    $notification = Notification::create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'target_date' => '2026-11-30',
        'reminder_type' => 'before_3_days',
        'message' => '目標日まであと3日です。',
        'read_at' => null,
    ]);

    // 既読日時を登録
    $notification->update([
        'read_at' => '2026-11-27 12:00:00',
    ]);

    // DBに既読日時が保存されたことを確認
    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'read_at' => '2026-11-27 12:00:00',
    ]);

    // datetime型として取得できることを確認
    $this->assertSame(
        '2026-11-27 12:00:00',
        $notification->fresh()->read_at->format('Y-m-d H:i:s')
    );
}

}

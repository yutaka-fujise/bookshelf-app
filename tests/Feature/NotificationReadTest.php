<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Notification;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(User $user): Notification
    {
        $book = Book::create([
            'title' => '既読テスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000070',
        ]);

        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        return Notification::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'target_date' => '2026-11-30',
            'reminder_type' => 'before_3_days',
            'message' => '既読テスト用通知',
            'read_at' => null,
        ]);
    }

    // 自分の未読通知を既読にできる
    public function test_owner_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification));

        $response->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    // 既読済みの通知は再操作しても既読日時が変わらない
    public function test_marking_read_notification_again_preserves_timestamp(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        try {
            $user = User::factory()->create();
            $notification = $this->createNotification($user);

            $notification->update([
                'read_at' => '2026-10-18 12:00:00',
            ]);

            $response = $this->actingAs($user)
                ->post(route('notifications.read', $notification));

            $response->assertRedirect(route('notifications.index'));

            $this->assertSame(
                '2026-10-18 12:00:00',
                $notification->fresh()->read_at->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    // 他人の通知は既読にできない
    public function test_other_user_cannot_mark_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->createNotification($owner);

        $response = $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification));

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    // 存在しない通知は404
    public function test_nonexistent_notification_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/notifications/999999/read');

        $response->assertNotFound();
    }

    // 未ログインユーザーは既読にできない
    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user);

        $response = $this->post(route('notifications.read', $notification));

        $response->assertRedirect(route('login'));
        $this->assertNull($notification->fresh()->read_at);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Notification;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(User $user, string $message): Notification
    {
        $book = Book::create([
            'title' => '通知一覧テスト用書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000' . str_pad((string) (Book::count() + 1), 3, '0', STR_PAD_LEFT),
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
            'message' => $message,
            'read_at' => null,
        ]);
    }

    // ログインユーザーは通知一覧を表示できる
    public function test_authenticated_user_can_view_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, '自分の読書計画のお知らせ');

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertSee('自分の読書計画のお知らせ');
    }

    // 他人の通知は表示されない
    public function test_index_displays_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createNotification($user, '自分だけの通知');
        $this->createNotification($otherUser, '他人だけの通知');

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertSee('自分だけの通知');
        $response->assertDontSee('他人だけの通知');
    }

    // 通知がない場合は案内文を表示する
    public function test_index_displays_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('通知はありません。');
    }

    // 未ログインユーザーは通知一覧を表示できない
    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }
}

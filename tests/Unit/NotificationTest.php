<?php

namespace Tests\Unit;

use App\Models\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    /**
     * 一括代入可能なカラムが正しく設定されていること
     */
    public function test_notification_has_correct_fillable_attributes(): void
    {
        $notification = new Notification();

        $this->assertSame([
            'user_id',
            'reading_plan_id',
            'target_date',
            'reminder_type',
            'message',
            'read_at',
        ], $notification->getFillable());
    }

    /**
     * 通知対象日が日付型にキャストされる設定であること
     */
    public function test_target_date_has_date_cast(): void
    {
        $notification = new Notification();

        $this->assertSame(
            'date',
            $notification->getCasts()['target_date']
        );
    }

    /**
     * 既読日時が日時型にキャストされる設定であること
     */
    public function test_read_at_has_datetime_cast(): void
    {
        $notification = new Notification();

        $this->assertSame(
            'datetime',
            $notification->getCasts()['read_at']
        );
    }
}

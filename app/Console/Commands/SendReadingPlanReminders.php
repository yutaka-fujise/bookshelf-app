<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:remind';

    protected $description = '読書計画の期日に応じてリマインダー通知を生成する';

    public function handle(): int
    {
        $today = today();

        $reminders = [
            [
                'target_date' => $today->copy()->addDays(3)->toDateString(),
                'reminder_type' => 'before_3_days',
                'message' => '読書計画の期日まであと3日です。',
            ],
            [
                'target_date' => $today->toDateString(),
                'reminder_type' => 'on_target_date',
                'message' => '今日は読書計画の期日です。',
            ],
            [
                'target_date' => $today->copy()->subDays(3)->toDateString(),
                'reminder_type' => 'after_3_days',
                'message' => '読書計画の期日から3日が経過しました。',
            ],
        ];

        $count = 0;

        foreach ($reminders as $reminder) {
            $plans = ReadingPlan::whereIn('status', ['in_progress', 'expired'])
                ->whereDate('target_date', $reminder['target_date'])
                ->get();

            foreach ($plans as $plan) {
                $notification = Notification::firstOrCreate(
                    [
                        'reading_plan_id' => $plan->id,
                        'target_date' => $reminder['target_date'],
                        'reminder_type' => $reminder['reminder_type'],
                    ],
                    [
                        'user_id' => $plan->user_id,
                        'message' => $reminder['message'],
                        'read_at' => null,
                    ]
                );

                if ($notification->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        $this->info("{$count}件のリマインダー通知を生成しました。");

        return self::SUCCESS;
    }
}

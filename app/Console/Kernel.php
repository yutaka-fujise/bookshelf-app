<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 毎日20時に読書計画のリマインダー通知を生成する
        $schedule->command('reading-plans:remind')
            ->dailyAt('20:00')
            ->timezone('Asia/Tokyo');

        // 毎日20時に期限切れの読書計画を更新する
        $schedule->command('reading-plans:expire')
            ->dailyAt('20:00')
            ->timezone('Asia/Tokyo');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
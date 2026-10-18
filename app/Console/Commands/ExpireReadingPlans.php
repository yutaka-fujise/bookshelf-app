<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class ExpireReadingPlans extends Command
{
    protected $signature = 'reading-plans:expire';

    protected $description = '期日を過ぎた読書計画を期限切れに更新する';

    public function handle(): int
    {
        $today = today()->toDateString();

        $count = ReadingPlan::where('status', 'in_progress')
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => 'expired',
            ]);

        $this->info("{$count}件の読書計画を期限切れに更新しました。");

        return self::SUCCESS;
    }
}

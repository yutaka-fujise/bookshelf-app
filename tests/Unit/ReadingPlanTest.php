<?php

namespace Tests\Unit;

use App\Models\ReadingPlan;
use PHPUnit\Framework\TestCase;

class ReadingPlanTest extends TestCase
{
    /**
     * 一括代入可能なカラムが正しく設定されていること
     */
    public function test_reading_plan_has_correct_fillable_attributes(): void
    {
        $plan = new ReadingPlan();

        $this->assertSame([
            'user_id',
            'book_id',
            'target_date',
            'status',
            'completed_at',
        ], $plan->getFillable());
    }

    /**
     * 目標日が日付型にキャストされる設定であること
     */
    public function test_target_date_has_date_cast(): void
    {
        $plan = new ReadingPlan();

        $this->assertSame(
            'date',
            $plan->getCasts()['target_date']
        );
    }

    /**
     * 完了日時が日時型にキャストされる設定であること
     */
    public function test_completed_at_has_datetime_cast(): void
    {
        $plan = new ReadingPlan();

        $this->assertSame(
            'datetime',
            $plan->getCasts()['completed_at']
        );
    }
}

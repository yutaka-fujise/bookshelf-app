<?php

namespace Tests\Unit;

use App\Models\Review;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    /**
     * 一括代入可能なカラムが正しく設定されていること
     */
    public function test_review_has_correct_fillable_attributes(): void
    {
        $review = new Review();

        $this->assertSame([
            'user_id',
            'book_id',
            'rating',
            'comment',
        ], $review->getFillable());
    }

    /**
     * 評価が整数型にキャストされる設定であること
     */
    public function test_rating_has_integer_cast(): void
    {
        $review = new Review();

        $this->assertSame(
            'integer',
            $review->getCasts()['rating']
        );
    }
}

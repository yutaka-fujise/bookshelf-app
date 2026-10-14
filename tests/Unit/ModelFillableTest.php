<?php

namespace Tests\Unit;

use App\Models\Genre;
use App\Models\Favorite;
use App\Models\ReviewLike;
use PHPUnit\Framework\TestCase;

class ModelFillableTest extends TestCase
{
    /**
     * Genreモデルの一括代入可能なカラムを確認する
     */
    public function test_genre_has_correct_fillable_attributes(): void
    {
        $genre = new Genre();

        $this->assertSame([
            'name',
        ], $genre->getFillable());
    }

    /**
     * Favoriteモデルの一括代入可能なカラムを確認する
     */
    public function test_favorite_has_correct_fillable_attributes(): void
    {
        $favorite = new Favorite();

        $this->assertSame([
            'user_id',
            'book_id',
        ], $favorite->getFillable());
    }

    /**
     * ReviewLikeモデルの一括代入可能なカラムを確認する
     */
    public function test_review_like_has_correct_fillable_attributes(): void
    {
        $reviewLike = new ReviewLike();

        $this->assertSame([
            'user_id',
            'review_id',
        ], $reviewLike->getFillable());
    }
}

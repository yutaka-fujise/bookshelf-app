<?php

namespace Tests\Unit;

use App\Models\Book;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    /**
     * 一括代入可能なカラムが正しく設定されていること
     */
    public function test_book_has_correct_fillable_attributes(): void
    {
        $book = new Book();

        $this->assertSame([
            'user_id',
            'title',
            'author',
            'isbn13',
            'published_at',
            'description',
            'image_url',
        ], $book->getFillable());
    }

    /**
     * 出版日が日付型にキャストされる設定であること
     */
    public function test_published_at_has_date_cast(): void
    {
        $book = new Book();

        $this->assertSame(
            'date',
            $book->getCasts()['published_at']
        );
    }
}

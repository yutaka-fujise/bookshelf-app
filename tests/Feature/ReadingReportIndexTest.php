<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingReportIndexTest extends TestCase
{
    use RefreshDatabase;

    // 未ログインユーザーはマイ読書レポートを閲覧できない
    public function test_guest_cannot_view_reading_report(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect(route('login'));
    }

    // 本人のレビュー数・読了冊数・平均評価を集計できる
    public function test_report_displays_own_summary_statistics(): void
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = Book::create([
            'title' => 'レポートテスト書籍1',
            'author' => 'テスト著者',
            'isbn13' => '9784000000100',
        ]);

        $book2 = Book::create([
            'title' => 'レポートテスト書籍2',
            'author' => 'テスト著者',
            'isbn13' => '9784000000101',
        ]);

        $book1->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても良かった',
        ]);

        $book2->reviews()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '普通でした',
        ]);

        // 他人のレビューは集計対象外
        $book1->reviews()->create([
            'user_id' => $otherUser->id,
            'rating' => 1,
            'comment' => '他人のレビュー',
        ]);

        // 同じ書籍の読了計画が複数あっても1冊と数える
        foreach ([$book1, $book1, $book2] as $book) {
            ReadingPlan::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'target_date' => '2026-11-30',
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        // 他人の読書計画は集計対象外
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $book1->id,
            'target_date' => '2026-11-30',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 2
                    && $stats['summary']['books_read'] === 2
                    && (float) $stats['summary']['average_rating'] === 4.0;
            });
    }

    // 本人の評価分布を星1〜5の順で集計できる
    public function test_report_displays_own_rating_distribution(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach ([5, 5, 3, 1] as $index => $rating) {
            $book = Book::create([
                'title' => "評価分布テスト書籍{$index}",
                'author' => 'テスト著者',
                'isbn13' => '97840000002' . sprintf('%02d', $index),
            ]);

            $book->reviews()->create([
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => '評価分布テスト',
            ]);
        }

        // 他人の評価は集計しない
        $otherBook = Book::create([
            'title' => '他人の評価分布テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000299',
        ]);

        $otherBook->reviews()->create([
            'user_id' => $otherUser->id,
            'rating' => 4,
            'comment' => '他人の評価',
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['rating_distribution']->values()->all()
                    === [1, 0, 1, 0, 2];
            });
    }

    // 本人の高評価書籍を評価順で最大5冊取得できる
    public function test_report_displays_top_five_highly_rated_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach ([3, 5, 4, 5, 4, 5, 4] as $index => $rating) {
            $book = Book::create([
                'title' => "高評価テスト書籍{$index}",
                'author' => 'テスト著者',
                'isbn13' => '97840000003' . sprintf('%02d', $index),
            ]);

            $book->reviews()->create([
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => '高評価テスト',
                'created_at' => now()->subDays(7 - $index),
            ]);
        }

        // 他人が高評価した書籍は対象外
        $otherBook = Book::create([
            'title' => '他人の高評価書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000399',
        ]);

        $otherBook->reviews()->create([
            'user_id' => $otherUser->id,
            'rating' => 5,
            'comment' => '他人のレビュー',
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                $books = $stats['top_rated_books'];

                return count($books) === 5
                    && collect($books)->pluck('title')->values()->all() === [
                        '高評価テスト書籍5',
                        '高評価テスト書籍3',
                        '高評価テスト書籍1',
                        '高評価テスト書籍6',
                        '高評価テスト書籍4',
                    ];
            });
    }

    // 本人のレビューからジャンル別評価TOP5を集計できる
    public function test_report_displays_top_five_genre_ratings(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $genres = collect();

        foreach (['小説', 'ミステリー', '歴史', '技術', '料理', '旅行'] as $name) {
            $genres->push(Genre::create(['name' => $name]));
        }

        // 各ジャンルに本人のレビューを登録
        foreach ([5, 4, 3, 2, 1, 5] as $index => $rating) {
            $book = Book::create([
                'title' => "ジャンル集計テスト書籍{$index}",
                'author' => 'テスト著者',
                'isbn13' => '97840000004' . sprintf('%02d', $index),
            ]);

            $book->genres()->attach($genres[$index]->id);

            // 1冊目は2つのジャンルに所属
            if ($index === 0) {
                $book->genres()->attach($genres[1]->id);
            }

            $book->reviews()->create([
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => 'ジャンル集計テスト',
            ]);
        }

        // 他人のレビューは集計対象外
        $otherBook = Book::create([
            'title' => '他人のジャンル集計書籍',
            'author' => 'テスト著者',
            'isbn13' => '9784000000499',
        ]);

        $otherBook->genres()->attach($genres[4]->id);

        $otherBook->reviews()->create([
            'user_id' => $otherUser->id,
            'rating' => 5,
            'comment' => '他人のジャンルレビュー',
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                $genres = collect($stats['genre_ratings']);

                return $genres->count() === 5
                    && $genres->pluck('name')->values()->all() === [
                        '小説',
                        '旅行',
                        'ミステリー',
                        '歴史',
                        '技術',
                    ]
                    && (float) $genres[2]['average_rating'] === 4.5
                    && (int) $genres[2]['count'] === 2;
            });
    }
}

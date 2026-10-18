<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(string $isbn): Book
    {
        return Book::create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn13' => $isbn,
        ]);
    }

    // ログインユーザーは読書計画を登録できる
    public function test_authenticated_user_can_store_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook('9784000000070');

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '2026-11-30',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    // 同じ書籍に進行中の計画があれば新規登録できない
    public function test_cannot_create_duplicate_in_progress_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook('9784000000070');

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-20',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '2026-12-20',
            ]);

        $response->assertSessionHasErrors('book_id');
        $this->assertDatabaseCount('reading_plans', 1);
    }

    // 読了済みの計画があっても同じ書籍を再計画できる
    public function test_can_create_new_plan_after_completed_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook('9784000000070');

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-10-01',
            'status' => 'completed',
            'completed_at' => '2026-09-30 12:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '2026-12-20',
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseCount('reading_plans', 2);
    }

    // 期限切れの計画があっても同じ書籍を再計画できる
    public function test_can_create_new_plan_after_expired_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook('9784000000070');

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-09-30',
            'status' => 'expired',
        ]);

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '2026-12-20',
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseCount('reading_plans', 2);
    }

    // 目標日は必須
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook('9784000000070');

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors('target_date');
        $this->assertDatabaseCount('reading_plans', 0);
    }

    // 書籍IDは必須
    public function test_book_id_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'target_date' => '2026-11-30',
            ]);

        $response->assertSessionHasErrors('book_id');
        $this->assertDatabaseCount('reading_plans', 0);
    }

    // 存在しない書籍では計画を登録できない
    public function test_nonexistent_book_cannot_be_planned(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => 999999,
                'target_date' => '2026-11-30',
            ]);

        $response->assertSessionHasErrors('book_id');
        $this->assertDatabaseCount('reading_plans', 0);
    }

    // ユーザーIDやステータスを送信しても上書きできない
    public function test_user_cannot_override_owner_or_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook('9784000000070');

        $response = $this->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => '2026-11-30',
                'user_id' => $otherUser->id,
                'status' => 'completed',
                'completed_at' => '2026-10-01 12:00:00',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->assertDatabaseCount('reading_plans', 1);
    }

    // 未ログインでは読書計画を登録できない
    public function test_guest_cannot_store_reading_plan(): void
    {
        $book = $this->createBook('9784000000070');

        $response = $this->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => '2026-11-30',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reading_plans', 0);
    }
}

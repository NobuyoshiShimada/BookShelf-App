<?php

namespace Tests\Feature\Web;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdvancedReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Book $book1;

    private Book $book2;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成
        $this->user = User::factory()->create([
            'email' => 'yamada@example.com',
        ]);

        $this->otherUser = User::factory()->create();
        // ジャンルの作成
        $genre = Genre::factory()->create(['name' => 'プログラミング']);

        // テスト用書籍の作成（検索、ソート、ISBNテスト用）
        $this->book1 = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel実践',
            'author' => 'テスト太郎',
            'isbn' => '1234567890123',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        $this->book1->genres()->attach($genre->id);

        $this->book2 = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'PHP問題集',
            'author' => 'サンプル次郎',
            'isbn' => '0987654321098',
            'created_at' => Carbon::now(),
        ]);
    }

    public function test_マイ読書レポート（集計）のテスト(): void
    {
        Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'rating' => 5,
        ]);

        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index'));
        $response->assertStatus(200)->assertViewHas('stats')->assertSee('1件');
    }

    public function test_読書計画のリマインダー通知、自動遷移状態バッチ(): void
    {
        // 期限超過
        $overduePlan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->subDays(6),
            'status' => ReadingPlanStatus::Reading->value,
        ]);

        // 期日3日前
        $reminderPlan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book2->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        // 期日当日
        $bookToday = Book::factory()->create([
            'user_id' => $this->user->id,
            'isbn' => '9999999999911',
        ]);
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $bookToday->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::Reading->value,
        ]);

        // 日次バッチ
        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);
        // 自動状態遷移バッチ:期日を超過した計画が'overdue'になっているか
        $this->assertDatabaseHas('reading_plans', [
            'id' => $overduePlan->id,
            'status' => ReadingPlanStatus::Overdue->value,
        ]);
        // リマインダー通知:DatabaseChannel（notificationsテーブル）に通知が届いているか
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
        ]);

        $this->assertEquals(3, $this->user->unreadNotifications->count());
    }

    public function test_読書計画の一覧をステータスで絞り込み(): void
    {
        // 読書中（reading）
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::Reading->value,
        ]);

        // 未読（unread）
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book2->id,
            'target_date' => Carbon::today()->addDays(14),
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        // 読了（completed）
        $bookCompleted = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => '読了済みの本',
            'isbn' => '9999999999991',
        ]);
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $bookCompleted->id,
            'target_date' => Carbon::today()->subDays(2),
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => Carbon::today()->subDays(2),
        ]);

        // 期日超過（overdue）
        $bookOverdue = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => '期日超過の本',
            'isbn' => '9999999999992',
        ]);
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $bookOverdue->id,
            'target_date' => Carbon::today()->subDays(5),
            'status' => ReadingPlanStatus::Overdue->value,
        ]);

        // 読書中のみ
        $response = $this->actingAs($this->user)->get(route('reading-plans.index', [
            'status' => 'reading',
        ]));
        $response->assertStatus(200)
            ->assertSee('Laravel実践')
            ->assertDontSee('PHP問題集')
            ->assertDontSee('読了済みの本')
            ->assertDontSee('期日超過の本');

        // 未読のみ
        $response = $this->actingAs($this->user)->get(route('reading-plans.index', [
            'status' => 'unread',
        ]));
        $response->assertStatus(200)
            ->assertSee('PHP問題集')
            ->assertDontSee('Laravel実践')
            ->assertDontSee('読了済みの本')
            ->assertDontSee('期日超過の本');

        // 読了のみ
        $response = $this->actingAs($this->user)->get(route('reading-plans.index', [
            'status' => 'completed',
        ]));
        $response->assertStatus(200)
            ->assertSee('読了済みの本')
            ->assertDontSee('Laravel実践')
            ->assertDontSee('PHP問題集')
            ->assertDontSee('期日超過の本');

        // 読了のみ
        $response = $this->actingAs($this->user)->get(route('reading-plans.index', [
            'status' => 'overdue',
        ]));
        $response->assertStatus(200)
            ->assertSee('期日超過の本')
            ->assertDontSee('Laravel実践')
            ->assertDontSee('PHP問題集')
            ->assertDontSee('読了済みの本');
    }

    public function test_読書計画の新規登録画面の表示と、登録処理(): void
    {
        // 新規登録画面の表示
        $this->actingAs($this->user)->get(route('reading-plans.create'))->assertStatus(200);

        $response = $this->actingAs($this->user)->post(route('reading-plans.store', [
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(30)->format('Y-m-d'),
        ]));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'status' => ReadingPlanStatus::Unread->value,
        ]);
    }

    public function test_読書計画の更新画面の表示、更新、読了、削除(): void
    {
        $plan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        // 編集画面
        $this->actingAs($this->user)->get(route('reading-plans.edit', $plan->id))->assertStatus(200);

        // 更新
        $newDate = Carbon::today()->addDays(10)->format('Y-m-d');
        $this->actingAs($this->user)->put(route('reading-plans.update', $plan->id), [
            'target_date' => $newDate,
        ])->assertRedirect(route('reading-plans.index'));

        // 読了
        $this->actingAs($this->user)->post(route('reading-plans.complete', $plan->id))->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        // 削除
        $this->actingAs($this->user)->delete(route('reading-plans.destroy', $plan->id))->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    public function test_他ユーザーが読書計画のポリシーで403でブロック(): void
    {
        $plan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        $this->actingAs($this->otherUser);

        // 他人の計画の各種操作に対してすべて 403 Forbidden が返るか検証
        $this->get(route('reading-plans.edit', $plan->id))->assertStatus(403);
        $this->put(route('reading-plans.update', $plan->id), ['target_date' => '2026-12-31'])->assertStatus(403);
        $this->post(route('reading-plans.complete', $plan->id))->assertStatus(403);
        $this->delete(route('reading-plans.destroy', $plan->id))->assertStatus(403);
    }
}

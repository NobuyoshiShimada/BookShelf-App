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

    /**
     * マイ読書レポート（ダッシュボード集計）の検証
     *
     * ユーザーに紐付くレビュー評価、および「完了（Completed）」状態の読書計画データが
     * レポート画面の集計ロジック（stats）に正しくカウントされ、ビュー上に反映（200）されるかを検証。
     */
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

    /**
     * 読書計画の日次バッチ処理（ステータス自動変更 ＆ リマインダー通知）の検証
     *
     * 「期限超過（Overdue）」および「期日前リマインダー」の境界条件となるテストデータを配置した状態で
     * 独自Artisanコマンドを実行した際、DB内のステータスが正しく書き換わり、かつ
     * 該当ユーザーに対してデータベース通知（notifications）が期待値通りの件数で発行されるかを検証。
     */
    public function test_読書計画のリマインダー通知、自動遷移状態バッチ(): void
    {
        $today = Carbon::today();
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
            'status' => ReadingPlanStatus::Reading->value,
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

        $this->assertEquals(2, $this->user->unreadNotifications->count());
    }

    /**
     * 読書計画一覧のステータス条件絞り込み検証
     *
     * 「読書中」「読了」「期日超過」の各ステータスを持つ計画データをDBに混在させた状態で、
     * それぞれの検索パラメータ（status）を付与してリクエストを送信した際、
     * 該当する書籍タイトルのみが画面に描画され、非該当データが完全に非表示（DontSee）となるかを検証。
     */
    public function test_読書計画の一覧をステータスで絞り込み(): void
    {
        // 読書中（reading）
        ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::Reading->value,
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

        // ④ 期日超過
        $response = $this->actingAs($this->user)->get(route('reading-plans.index', [
            'status' => 'overdue',
        ]));
        $response->assertStatus(200)
            ->assertSee('期日超過の本')
            ->assertDontSee('Laravel実践')
            ->assertDontSee('PHP問題集')
            ->assertDontSee('読了済みの本');
    }

    /**
     * 読書計画の新規登録画面の遷移およびデータ永続化処理
     *
     * 認証済みユーザーが計画作成画面（create）にアクセスして正常表示（200）されること、
     * および対象書籍IDと目標日付をPOST送信した際に、一覧画面（index）へリダイレクトされ、
     * 初期ステータス（Reading）を伴う読書計画レコードがDBへ安全に保存されるかを検証。
     */
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
            'status' => ReadingPlanStatus::Reading->value,
        ]);
    }

    /**
     * 読書計画の編集・更新・読了・削除に関する一連のライフサイクルテスト
     *
     * 登録された読書計画（ReadingPlan）に対し、所有者本人が「編集画面の表示（200）」「目標日数の更新（PUT）」
     * 「読了ステータスへの遷移（POST ➔ DB確認）」「レコードの物理削除（DELETE ➔ DB不在確認）」の
     * すべてのフェーズをエラーなく正常に完結させ、一覧画面へ正しくリダイレクトされるかを検証。
     */
    public function test_読書計画の更新画面の表示、更新、読了、削除(): void
    {
        $plan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::Reading->value,
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

    /**
     * 認可ポリシー（Policy）による他人の読書計画操作の完全ブロック検証
     *
     * 所有権を持たない別ユーザー（otherUser）としてログインした際、他人が作成した読書計画の
     * 「編集画面の表示」「情報の更新」「読了処理」「削除処理」のすべてのアクションにおいて
     * 認可ポリシーが正しく介入し、安全に 403 Forbidden で遮断されるかを一括検証。
     */
    public function test_他ユーザーが読書計画のポリシーで403でブロック(): void
    {
        $plan = ReadingPlan::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::Reading->value,
        ]);

        $this->actingAs($this->otherUser);

        // 他人の計画の各種操作に対してすべて 403 Forbidden が返るか検証
        $this->get(route('reading-plans.edit', $plan->id))->assertStatus(403);
        $this->put(route('reading-plans.update', $plan->id), ['target_date' => '2026-12-31'])->assertStatus(403);
        $this->post(route('reading-plans.complete', $plan->id))->assertStatus(403);
        $this->delete(route('reading-plans.destroy', $plan->id))->assertStatus(403);
    }
}

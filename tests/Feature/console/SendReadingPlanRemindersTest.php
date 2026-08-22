<?php

namespace Tests\Feature\Console;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * 3日前、当日、3日後でそれぞれの通知タイトルと本文が
     * 正しくマッチングされ、配信されているかを検証
     */
    public function test_各タイミングで個別に正しいメッセージが配信される(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        // 1. 期日3日前のデータを作成
        $book1 = Book::factory()->create(['user_id' => $user->id, 'title' => '3日前用の書籍']);
        $plan3DaysBefore = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'target_date' => $today->copy()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        // 2. 期日当日のデータを作成
        $book2 = Book::factory()->create(['user_id' => $user->id, 'title' => '当日用の書籍']);
        $planToday = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'target_date' => $today,
            'status' => ReadingPlanStatus::Reading,
        ]);

        // 3. 期日3日後のデータを作成
        $book3 = Book::factory()->create(['user_id' => $user->id, 'title' => '3日後用の書籍']);
        $plan3DaysAfter = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'target_date' => $today->copy()->subDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        // コマンド実行
        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        // 3日前の通知アサーション
        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification, $channels, $notifiable) {
            // toArray() を呼び出して protected の壁を越え、パブリックな配列データを取得
            $data = $notification->toArray($notifiable);

            // 通知クラスの仕様に合わせて、キー名（'timing', 'title', 'body'）をアサート
            return ($data['timing'] ?? '') === '3日前'
                && str_contains($data['title'] ?? '', '近づいています')
                && str_contains($data['body'] ?? '', 'あと3日です');
        });

        // 当日の通知アサーション
        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification, $channels, $notifiable) {
            $data = $notification->toArray($notifiable);

            return ($data['timing'] ?? '') === '当日'
                && str_contains($data['title'] ?? '', '期日当日です')
                && str_contains($data['body'] ?? '', '期日当日です');
        });

        // 3日後の通知アサーション
        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification, $channels, $notifiable) {
            $data = $notification->toArray($notifiable);

            return ($data['timing'] ?? '') === '3日後'
                && str_contains($data['title'] ?? '', '3日過ぎています')
                && str_contains($data['body'] ?? '', '3日が経過しました');
        });
    }

    /**
     * すでに status が Overdue になっている過去の計画は、
     * 自動状態遷移のカウント（statusUpdateCount）に含まれないことを検証
     */
    public function test_すでに期限超過済みの計画はステータス更新カウントに加算されない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $today = Carbon::today();

        // すでに status が Overdue になっている10日前の過去データを作成
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $today->copy()->subDays(10),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        // コマンドを実行し、ステータス変更の案内が「0件」であることを出力チェック
        $this->artisan('app:send-reading-plan-reminders')
            ->expectsOutputToContain('期限超過に遷移した計画: 0 件')
            ->assertExitCode(0);
    }
}

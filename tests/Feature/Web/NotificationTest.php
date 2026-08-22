<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected ReadingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $this->user->id]);
        $this->plan = ReadingPlan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today(),
        ]);
    }

    /**
     * ユーザー本人の通知一覧画面（index）が正しく表示され、
     * 自分宛ての通知メッセージが画面上で目視できるかを検証
     */
    public function test_通知一覧画面が正常に表示され自分宛ての通知が確認できる(): void
    {
        // 自分宛ての通知を1件
        $this->user->notify(new ReadingPlanReminder($this->plan, '当日', '期日当日です。', '「テスト書籍」の期日です。'));

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        // 画面上に通知のタイトルや本文が含まれているかアサート
        $response->assertSee('期日当日です。');
    }

    /**
     * 自分の未読通知に対して既読化（read）をリクエストした際、
     * 正常に read_at が記録され、元の画面（back）へリダイレクトされるかを検証
     */
    public function test_自分の未読通知を正常に既読にマークできる(): void
    {
        // 未読通知を生成
        $this->user->notify(new ReadingPlanReminder($this->plan, '当日', 'タイトル', '本文'));

        $notification = $this->user->unreadNotifications->first();
        $this->assertNull($notification->read_at); // 最初は未読

        // 通知一覧画面から「既読ボタン」を押した状況を再現
        $response = $this->actingAs($this->user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $notification));

        // 直前の画面（index）へ安全にリダイレクトされるか検証
        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success', '通知を既読にしました。');

        // データベース（またはインスタンス）上で read_at が現在時刻に更新されたかアサート
        $this->assertNotNull($notification->fresh()->read_at);
    }
}

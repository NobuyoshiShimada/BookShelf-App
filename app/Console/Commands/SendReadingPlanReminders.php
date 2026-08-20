<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendReadingPlanReminders extends Command
{
    /**
     * コンソールコマンドの識別シグネチャ（Artisan命令名）
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * コンソールコマンドの機能概要説明
     *
     * @var string
     */
    protected $description = '読書計画の期日判定（3日前、当日、3日後超過）期日を過ぎた計画の自動状態遷移を行います。';

    /**
     * 登録されたタスクスケジュールから自動実行されるバッチ処理のメインロジック
     *
     * @return int コマンドの終了ステータスコード（正常終了時は 0）
     */
    public function handle(): int
    {
        $today = Carbon::today();

        $plans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->get();

        $notificationGroups = [
            '3日前' => ['users' => collect(), 'plans' => collect()],
            '当日'  => ['users' => collect(), 'plans' => collect()],
            '3日後' => ['users' => collect(), 'plans' => collect()],
        ];

        $notificationCount = 0;
        $statusUpdateCount = 0;

        foreach ($plans as $plan) {
            if (!$plan->user || !$plan->book) {
                continue;
            }

            $targetDate = Carbon::parse($plan->target_date)->startOfDay();
            $daysDifference = $today->diffInDays($targetDate, false);

            // 自動状態遷移

            if ($daysDifference < 0 && $plan->status->value !== ReadingPlanStatus::Overdue->value) {
                $plan->update([
                    'status' => ReadingPlanStatus::Overdue->value,
                ]);

                $statusUpdateCount++;
            }

            // リマインダー通知の自動配信
            if ($daysDifference === 3) {
                $notificationGroups['3日前']['users']->push($plan->user);
                $notificationGroups['3日前']['plans']->push($plan);
            } elseif ($daysDifference === 0) {
                $notificationGroups['当日']['users']->push($plan->user);
                $notificationGroups['当日']['plans']->push($plan);
            } elseif ($daysDifference === -3) {
                $notificationGroups['3日後']['users']->push($plan->user);
                $notificationGroups['3日後']['plans']->push($plan);
            }
        }

        foreach ($notificationGroups as $timing => $data) {
            if ($data['users']->isEmpty()) {
                continue;
            }


            $title = match ($timing) {
                '3日前' => '読書期日が近づいています。',
                '当日'  => '読書計画の期日当日です。',
                '3日後' => '読書期日が3日過ぎています。',
            };

            foreach ($data['plans'] as $index => $plan) {
                $user = $data['users'][$index];
                $body = match ($timing) {
                    '3日前' => "「{$plan->book->title}」の読書期日まであと3日です。",
                    '当日'  => "「{$plan->book->title}」の読書期日当日です。",
                    '3日後' => "「{$plan->book->title}」の読書期日から3日が経過しました。",
                };
                Notification::send($user, new ReadingPlanReminder($plan, $timing, $title, $body));
                $notificationCount++;
            }
        }

        $this->info('処理が完了しました。');
        $this->info("・自動配信された通知: {$notificationCount} 件");
        $this->info("・期限超過に遷移した計画: {$statusUpdateCount} 件");

        return 0;
    }
}

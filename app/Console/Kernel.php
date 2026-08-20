<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーション全体の定期タスク（バックグラウンドジョブ）のスケジュール定義
     *
     * 💡 登録仕様:
     * 読書計画の自動期日判定 ＆ 通知配信バッチコマンドを、毎日深夜0:00に自動実行（daily）するように登録しています。
     *
     * @param  Schedule  $schedule  タスクスケジュールを管理するオブジェクト
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('app:send-reading-plan-reminders')
        ->daily();
    }

    /**
     * アプリケーション専用のカスタム Artisan コマンドの登録・スキャン処理
     *
     * `app/Console/Commands` フォルダ内のすべてのコマンドファイルを自動ロードし、
     * 合わせてコンソール用ルーティング（`routes/console.php`）を読み込みます。
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    protected $readingPlan;

    protected $timing;

    protected $title;

    protected $body;

    /**
     * 新しい通知リマインダーインスタンスの生成
     *
     * @param  ReadingPlan  $readingPlan  対象の読書計画モデル
     * @param  string  $timing  通知タイミング識別子文字列
     * @param  string  $title  配信する通知タイトル文字列
     * @param  string  $body  配信する通知本文文字列
     */
    public function __construct(ReadingPlan $readingPlan, string $timing, string $title, string $body)
    {
        $this->readingPlan = $readingPlan;
        $this->timing = $timing;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * 通知を配信する対象チャンネル（配信経路）の定義
     *
     * 💡 実装仕様:
     * 本システムでは画面内通知一覧および未読カウントUIと連動させるため、
     * database チャンネルのみを採用して永続化します。
     *
     * @param  object  $notifiable  通知を受信する対象のエンティティ（Userモデル等）
     * @return array<int, string> 配信チャンネル名の配列
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * database チャンネルの `data` カラム（JSON型）にシリアライズして保存する配列構造の定義
     *
     * @param  object  $notifiable  通知を受信する対象のエンティティ（Userモデル等）
     * @return array<string, mixed> データベースにJSONとして格納する通知データの連想配列
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_title' => $this->readingPlan->book->title ?? '不明な書籍',
            'timing' => $this->timing,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}

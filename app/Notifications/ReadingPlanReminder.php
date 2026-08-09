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
     * 新しい通知インスタンスの作成
     *
     * @param ReadingPlan $readingPlan 読書計画モデル
     * @param string $timing 'three_days_before' | 'on_due_date' | 'three_days_after' など
     * @param string $title 通知のタイトル
     * @param string $body 通知の本文
     */

    /**
     * Create a new notification instance.
     */
    public function __construct(ReadingPlan $readingPlan, string $timing, string $title, string $body)
    {
        $this->readingPlan = $readingPlan;
        $this->timing = $timing;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
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

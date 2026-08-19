<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログイン中の認証ユーザー宛てのリマインダー通知一覧画面を表示
     *
     * @return View 通知一覧画面のビュー
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->notifications;

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 指定された未読通知を既読にマーク処理し、直前の画面へリダイレクト
     *
     * @param  string  $id  通知レコードのUUID（文字列型）
     * @return RedirectResponse 直前の画面へのリダイレクトレスポンス
     *
     * @throws ModelNotFoundException 対象の未読通知が存在しない場合
     */
    public function read(string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user->unreadNotifications()->findOrFail($id);

        $notification->markAsRead();

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}

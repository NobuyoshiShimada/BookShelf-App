<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->notifications;

        return view('notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()->findOrFail($id);

        $notifications->markAsRead();

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}

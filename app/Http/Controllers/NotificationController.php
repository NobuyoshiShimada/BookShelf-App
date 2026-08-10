<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;


class NotificationController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $notifications = $user->notifications;

        return view('notifications.index', compact('notifications'));
    }

    public function read($id)
    {
         /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()->findOrFail($id);

        $notifications->markAsRead();

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}

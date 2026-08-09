<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;


class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications;

        return view('notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        $notifications = Auth::user()->unreadNotifications()->findOrFail($id);

        $notifications->markAsRead();

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}

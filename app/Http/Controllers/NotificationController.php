<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->with('task')
            ->latest()
            ->paginate(30);

        // Mark all as read on page visit
        UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, UserNotification $notification)
    {
        abort_if($notification->user_id !== Auth::id(), 403);
        $notification->markRead();

        if ($notification->url) {
            return redirect($notification->url);
        }
        return back();
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }
}

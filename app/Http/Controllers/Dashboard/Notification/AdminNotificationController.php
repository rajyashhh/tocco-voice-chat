<?php

namespace App\Http\Controllers\Dashboard\Notification;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function count()
    {
        $count = AdminNotification::where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function list()
    {
        $notifications = AdminNotification::latest()->limit(20)->get();

        return view('admin.notifications.list', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
    public function markAllRead()
    {
        AdminNotification::unreadNotifications()
            ->update(['read_at' => now(), 'is_read' => true]);

        return response()->json(['status' => true, 'message' => 'تم تمييز جميع الإشعارات كمقروءة']);
    }
}

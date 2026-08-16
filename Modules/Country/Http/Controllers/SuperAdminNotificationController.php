<?php

namespace Modules\Country\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Country\Entities\SuperAdminNotification;

class SuperAdminNotificationController extends Controller
{
    public function count()
    {
        $count = SuperAdminNotification::where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function list()
    {
        $notifications = SuperAdminNotification::latest()->limit(20)->get();

        return view('SuperAdmin::notifications.list', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = SuperAdminNotification::findOrFail($id);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
    public function markAllRead()
    {
        SuperAdminNotification::unreadNotifications()
            ->update(['read_at' => now(), 'is_read' => true]);

        return response()->json(['status' => true, 'message' => 'تم تمييز جميع الإشعارات كمقروءة']);
    }
}

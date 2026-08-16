<?php

namespace App\Traits;

use App\Services\OctaneBroadcasterService;

/**
 * Trait للإرسال الآمن للـ broadcast events في Octane
 * يضمن أن broadcaster يستخدم بيانات свежة من قاعدة البيانات
 */
trait BroadcastsWithFreshCredentials
{
    /**
     * Fire event with fresh broadcaster credentials
     * استخدم هذا بدلاً من event() مباشرة عند إرسال broadcast events
     */
    protected static function broadcastEvent($event)
    {
        // في بيئة Octane، أعد بناء broadcaster قبل البث
        if (app()->runningInOctane()) {
            OctaneBroadcasterService::rebuildBroadcaster();
        }

        // الآن أرسل الـ event مع broadcaster جديد
        return event($event);
    }
}

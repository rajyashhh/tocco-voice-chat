<?php

namespace App\Observers;

use App\Models\Gift;
use Illuminate\Support\Facades\Cache;

class GiftObserver
{
    /**
     * Handle the Gift "saved" event (fires on both create and update).
     *
     * Centralizes API gift-catalog invalidation so every model write
     * (admin form, services, single-model updates) busts the cache.
     */
    public function saved(Gift $gift): void
    {
        try {
            Cache::tags(['gifts'])->flush();
            // Also bust the per-gift price/availability cache used in the send-gift charge path (GiftLogService).
            Cache::forget("gift_{$gift->id}");
        } catch (\Exception $e) {
            \Log::warning('Failed to flush gifts cache in GiftObserver@saved: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Gift "created" event.
     */
    public function created(Gift $gift): void
    {
        if ($gift->enable) {
            settings()->set('gifts_update_at', time());
        }
    }

    /**
     * Handle the Gift "updated" event.
     */
    public function updated(Gift $gift): void
    {
        $isEnableOld = $gift->getOriginal('enable');
        $svgOld = $gift->getOriginal('show_img');

        if ((!$isEnableOld && $gift->enable) || ($isEnableOld && !$gift->enable) || $gift->show_img != $svgOld) {
            settings()->set('gifts_update_at', time());
        }
    }

    /**
     * Handle the Gift "deleted" event.
     */
    public function deleted(Gift $gift): void
    {
        if ($gift->enable) {
            settings()->set('gifts_update_at', time());
        }

        try {
            Cache::tags(['gifts'])->flush();
            // Also bust the per-gift price/availability cache used in the send-gift charge path (GiftLogService).
            Cache::forget("gift_{$gift->id}");
        } catch (\Exception $e) {
            \Log::warning('Failed to flush gifts cache in GiftObserver@deleted: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ware;

class WareObserver
{
    /**
     * Handle the Ware "created" event.
     */
    public function created(Ware $ware): void
    {
        $timezone = getTimezone();
        $timestamp = Carbon::now($timezone)->timestamp;
        if ($ware->enable) {
            if ($ware->type == 6) {
                settings()->set('intro_updated_at', $timestamp);
            } elseif ($ware->type == 4) {
                settings()->set('frame_updated_at', $timestamp);
            } elseif ($ware->type == 1) {
                settings()->set('extra_updated_at', $timestamp);
            } elseif ($ware->type == 5) {
                settings()->set('bubble_frame_updated_at', $timestamp);
            } elseif ($ware->type == 12) {

                settings()->set('wappel_frame_updated_at', $timestamp);
            } elseif ($ware->type == 28) {

                settings()->set('profile_frame_updated', $timestamp);
            }
        }
    }

    /**
     * Handle the Ware "updated" event.
     */
    public function updated(Ware $ware): void
    {
        $isEnableOld = $ware->getOriginal('enable');
        $svgOld = $ware->getOriginal('img2');
        $timestamp = Carbon::now()->timestamp;
        if ($ware->type == 12) {
            settings()->set('wappel_frame_updated_at',  $timestamp);
        } elseif ($ware->type == 28) {
            settings()->set('profile_frame_updated',  $timestamp);
        }
        if ((!$isEnableOld && $ware->enable) || ($isEnableOld && !$ware->enable) || $svgOld != $ware->img2) {
            if ($ware->type == 6) {
                settings()->set('intro_updated_at',  $timestamp);
            } elseif ($ware->type == 4) {
                settings()->set('frame_updated_at',  $timestamp);
            } elseif ($ware->type == 1) {
                settings()->set('extra_updated_at',  $timestamp);
            }
        }



        if ($ware->type == 5 && ($ware->isDirty('top') ||
            $ware->isDirty('left') ||
            $ware->isDirty('right') ||
            $ware->isDirty('bottom'))) {
            settings()->set('bubble_frame_updated_at', $timestamp);
        } elseif ($ware->type == 12 && ($ware->isDirty('key_json'))) {
            settings()->set('wappel_frame_updated_at', $timestamp);
        }


        $originalSpecialValue = $ware->getOriginal('value');
        if ($ware->type == 25 && $originalSpecialValue != $ware->value) {
            User::where('special_id', $originalSpecialValue)
                ->update(['special_id' => $ware->value]);
        }
    }

    /**
     * Handle the Ware "deleted" event.
     */
    public function deleted(Ware $ware): void
    {
        $timezone = getTimezone();
        $timestamp = Carbon::now($timezone)->timestamp;
        if ($ware->enable) {
            if ($ware->type == 6) {
                settings()->set('intro_updated_at', $timestamp);
            } elseif ($ware->type == 4) {
                settings()->set('frame_updated_at', $timestamp);
            } elseif ($ware->type == 1) {
                settings()->set('extra_updated_at', $timestamp);
            } elseif ($ware->type == 5) {
                settings()->set('bubble_frame_updated_at', $timestamp);
            }
        }
    }
}

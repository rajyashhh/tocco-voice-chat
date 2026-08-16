<?php

namespace App\Observers;

use App\Models\Emoji;
use Illuminate\Support\Facades\Cache;

class EmojiObserver
{
    /**
     * Handle the Emoji "saved" event (fires on both create and update).
     *
     * @return void
     */
    public function saved(Emoji $emoji)
    {
        Cache::tags(['emojis'])->flush();
    }

    /**
     * Handle the Emoji "created" event.
     *
     * @return void
     */
    public function created(Emoji $emoji)
    {
        if ($emoji->enable) {
            settings()->set('emoji_updated', time());
        }
    }

    /**
     * Handle the Emoji "updated" event.
     *
     * @return void
     */
    public function updated(Emoji $emoji)
    {
        $isEnableOld = $emoji->getOriginal('enable');
        $svgOld = $emoji->getOriginal('emoji');

        if ((!$isEnableOld && $emoji->enable) || ($isEnableOld && !$emoji->enable) || $emoji->emoji != $svgOld) {
            settings()->set('emoji_updated_at', time());
        }
    }

    /**
     * Handle the Emoji "deleted" event.
     *
     * @return void
     */
    public function deleted(Emoji $emoji)
    {
        if ($emoji->enable) {
            settings()->set('emoji_updated_at', time());
        }

        Cache::tags(['emojis'])->flush();
    }
}

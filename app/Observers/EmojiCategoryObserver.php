<?php

namespace App\Observers;

use App\Models\EmojiCategory;
use Illuminate\Support\Facades\Cache;

class EmojiCategoryObserver
{
    /**
     * Handle the EmojiCategory "saved" event (fires on both create and update).
     */
    public function saved(EmojiCategory $emojiCategory): void
    {
        $this->clearCache();
    }

    /**
     * Handle the EmojiCategory "deleted" event.
     */
    public function deleted(EmojiCategory $emojiCategory): void
    {
        $this->clearCache();
    }

    /**
     * Invalidate the API emoji caches.
     */
    protected function clearCache(): void
    {
        try {
            Cache::tags(['emojis'])->flush();
        } catch (\Exception $e) {
            \Log::warning('Failed to clear tagged cache in EmojiCategoryObserver: ' . $e->getMessage());
        }
    }
}

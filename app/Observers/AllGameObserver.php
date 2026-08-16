<?php

namespace App\Observers;

use App\Models\AllGame;
use Illuminate\Support\Facades\Cache;

class AllGameObserver
{
    /**
     * Invalidate the per-game URL cache (allgame_url_{custom_id}) read by
     * NewLeaderCCGameController::urlGames on any AllGame write. Covers the panel
     * import (create/update) and the repository CRUD paths. Forgets both the new
     * and original custom_id so a custom_id change clears the stale entry.
     */
    public function saved(AllGame $game): void
    {
        $this->forget($game);
    }

    public function deleted(AllGame $game): void
    {
        $this->forget($game);
    }

    private function forget(AllGame $game): void
    {
        if ($game->custom_id !== null) {
            Cache::forget('allgame_url_' . $game->custom_id);
        }

        $original = $game->getOriginal('custom_id');
        if ($original !== null && $original !== $game->custom_id) {
            Cache::forget('allgame_url_' . $original);
        }
    }
}

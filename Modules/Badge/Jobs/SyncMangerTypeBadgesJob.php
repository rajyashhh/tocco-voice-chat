<?php

namespace Modules\Badge\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Badge\Helpers\MangerTypeBadgeHelper;

/**
 * Reconcile the position (manger_type) badges for every user holding a given
 * position. Runs after a badge is linked to / unlinked from the position.
 *
 * Chunked so switching thousands of users never happens synchronously, and
 * idempotent: re-running converges to the current linkage without duplicates.
 */
class SyncMangerTypeBadgesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $mangerTypeId
    ) {}

    public function handle(): void
    {
        User::query()
            ->where('manger_type_id', $this->mangerTypeId)
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    MangerTypeBadgeHelper::resyncUser($user, $this->mangerTypeId);
                }
            });
    }
}
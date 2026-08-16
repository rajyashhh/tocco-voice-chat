<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Milestones\Entities\Milestone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Modules\Milestones\Helpers\MilestoneHelper;

class MilestoneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    protected $milestoneId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $milestoneId)
    {
        $this->milestoneId = $milestoneId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $milestone = Milestone::with('rewards')->findOrFail($this->milestoneId);

        $usersQuery = match ($milestone->slug) {
            'country' => User::where('is_super_admin', 1),
            'bd' => User::where('is_bd', 1),
            'host-agency-owner' => User::whereHas('hasHostAgency'),
            'charge-agency-owner' => User::whereHas('hasShippingAgencyV2'),
            'family-owner' => User::whereHas('hasFamily'),
            'host' => User::where('type_user', 1),
            'region' => User::where('is_area_manager', 1),
            default => User::query(),
        };


        $processedCount = 0;
        $failedCount = 0;

        if ($milestone->slug == 'charge-agency-owner') {

            $milestone = Milestone::with('rewards')->where('slug', 'charge-agency-owner')->first();
            User::whereHas('hasHostAgency')->chunk(100, function ($users) use ($milestone, &$processedCount, &$failedCount) {
                foreach ($users as $user) {
                    try {
                        DB::transaction(function () use ($user, $milestone) {
                            MilestoneHelper::removeReward($user, $milestone->slug);
                        });

                        $processedCount++;
                        Log::debug("Processed milestone '{$milestone->slug}' for user {$user->id}");
                    } catch (\Exception $e) {
                        $failedCount++;
                        Log::error("MilestoneJob failed for user {$user->id}", [
                            'milestone_slug' => $milestone->slug,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });
        }


        $usersQuery->chunk(100, function ($users) use ($milestone, &$processedCount, &$failedCount) {
            foreach ($users as $user) {
                try {
                    DB::transaction(function () use ($user, $milestone) {
                        MilestoneHelper::removeReward($user, $milestone->slug);
                        MilestoneHelper::grantMilestoneToUser($user, $milestone->slug);
                    });

                    $processedCount++;
                    Log::debug("Processed milestone '{$milestone->slug}' for user {$user->id}");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("MilestoneJob failed for user {$user->id}", [
                        'milestone_slug' => $milestone->slug,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("MilestoneJob completely failed", [
            'milestone_id' => $this->milestoneId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

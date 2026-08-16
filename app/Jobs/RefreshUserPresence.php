<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class RefreshUserPresence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     *
     * Accepts only scalars to keep the serialized payload small and avoid
     * stale model/request state. The presence side-effects (device token,
     * online time, location, country) are applied in handle().
     *
     * $locale carries the request locale (set by the Localization middleware
     * from X-localization) so the worker re-applies it before the writes —
     * updateOnlineTime persists $user->lan = app()->getLocale(), which drives
     * push-notification language. Without this, the worker's default locale
     * would silently overwrite every user's language on cold start.
     */
    public function __construct(
        public int $userId,
        public ?string $deviceToken,
        public $lat,
        public $long,
        public ?string $iso,
        public ?string $locale = null
    ) {
        // Run on default queue to not block critical operations
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(UserService $userService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("RefreshUserPresence: User not found", ['user_id' => $this->userId]);
            return;
        }

        // Re-apply the request locale so updateOnlineTime persists the user's
        // real language (not the worker default) — see constructor docblock.
        if ($this->locale) {
            App::setLocale($this->locale);
        }

        $userService->applyPresenceUpdates($user, $this->deviceToken, $this->lat, $this->long, $this->iso);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RefreshUserPresence: Failed permanently after {$this->tries} attempts", [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}

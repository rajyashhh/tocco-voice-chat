<?php

namespace App\Jobs;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Public\Http\Services\UserCounterServices;

class UserVisitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $authId, private int $userId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $users = User::select(['id', 'name', 'notification_id', 'lan'])->with('profile:id,user_id,avatar')->whereIn('id', [$this->userId,$this->authId])->get();

        $user = $users->where('id', $this->userId)->first();
        $auth = $users->where('id', $this->authId)->first();

        if (!$auth || !$user || ($auth && Common::checkPackPrev($auth->id, 19))) return;

        $user->profileVisits()->syncWithoutDetaching([
            $this->authId => [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        CustomNotification::visitProfile($user, $auth);
        (new UserCounterServices)->eventUser($user, 'visit-profile');
    }
}

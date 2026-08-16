<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\GameWallet;
use Illuminate\Bus\Queueable;
use App\Facades\CustomNotification;
use Modules\Chat\Entities\ChatRoom;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Public\Http\Services\UserCounterServices;

class FollowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $receiver;
    private $user;

    public function __construct($user, $receiver)
    {
        $this->user = $user;
        $this->receiver = $receiver;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user_id = $this->user->id;
        $user_id2 = $this->receiver->id;
        $updateType = ChatRoom::where(function ($q) use ($user_id, $user_id2) {
            $q->where('user_id', $user_id)
                ->where('user_id2', $user_id2);
        })
            ->orWhere(function ($q) use ($user_id, $user_id2) {
                $q->where('user_id', $user_id2)
                    ->where('user_id2', $user_id);
            })
            ->update(['type' => 'friends']);
        if ($this->user->followBack($this->receiver)) {
            CustomNotification::followBack($this->receiver, $this->user);
            (new UserCounterServices)->UpgradeDateForType($this->receiver, 'friend');
            (new UserCounterServices)->eventUser($this->receiver, 'friend', 1);
        } else {
            CustomNotification::follow($this->receiver, $this->user);
            (new UserCounterServices)->UpgradeDateForType($this->receiver, 'followeds');
            (new UserCounterServices)->eventUser($this->receiver, 'follow', 1);
        }

        (new UserCounterServices)->eventUser($this->receiver, 'follower', 1);
    }
}

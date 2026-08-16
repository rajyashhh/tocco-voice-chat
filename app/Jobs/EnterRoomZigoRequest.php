<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\Pack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnterRoomZigoRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $roomId;
    /**
     * @var false
     */
    private $hasVip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $roomId, $hasVip = false)
    {
        //
        $this->user   = $user;
        $this->roomId = $roomId;
        $this->hasVip = $hasVip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ifIntroExist = false;
        if ($this->user->dress_3) {
            $ifIntroExist = Pack::query()
                                ->where('user_id', $this->user->id)
                                ->where('target_id', $this->user->dress_3)
                                ->where('type', 6)
                                ->where(function ($q) {
                                    $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
                                })
                                ->exists();
        }

        $this->user->withoutAppends = false;
        $d                          = [
            "messageContent" => [
                "message"    => "userEntro",
                "entroImg"   => $this->user->intro ?? '',
                "entroImgId" => $ifIntroExist ? (string)$this->user->dress_3 : "",
                'userName'   => $this->user->name ?: $this->user->nickname,
                'userImge'   => $this->user->avatar,
                'vip'        => $this->hasVip,
                'uid'        => $this->user->id
            ]
        ];

        $json = json_encode($d);

        Common::sendToStream('SendCustomCommand', $this->roomId, $this->user->id, $json);
        if (!Common::hasInPack($this->user->id, 17, true)) {
            Common::sendToStream_2('SendBroadcastMessage', $this->roomId, $this->user->id, $this->user->name, ' انضم للغرفة');
        }
    }
}

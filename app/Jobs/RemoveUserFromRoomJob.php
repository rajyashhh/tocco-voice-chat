<?php

namespace App\Jobs;

use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveUserFromRoomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public  $user_id;
    /**
     * Create a new job instance.
     */
    public function __construct( $user_id)
    {
       $this->$user_id =$user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->user_id);
        if($user)
        {
            $room = Room::where('uid',$user->now_room_uid)->first();
            if($room){

                $user->now_room_uid = 0;
                $user->update();

                $room->count_room_socket -=1 ;
                $room->update();

            }
        }

    }
}

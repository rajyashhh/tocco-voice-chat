<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Repositories\Room\RoomTopUsersRepository;
use App\Models\Room;
use App\Helpers\Common;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRoomCoinsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $roomId;
    public $totalPrice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $roomId, $totalPrice)
    {
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->totalPrice = $totalPrice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RoomTopUsersRepository $roomTopUsersRepository)
    {
        $room = Room::find($this->roomId);
        $user = User::find($this->userId);

        if (!$room || !$user) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($room, $user, $roomTopUsersRepository) {
            $roomTopUser = $roomTopUsersRepository->findOrCreate($room->id, $user->id);
            $roomTopUser->coins += $this->totalPrice;
            $roomTopUser->save();
        });
    }
}

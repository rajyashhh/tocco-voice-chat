<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Public\Http\Services\UpgradeRoomLevelServices;
use App\Models\Room;

class UpdateRoomLevelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $roomId;
    public $totalPrice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($roomId, $totalPrice)
    {
        $this->roomId = $roomId;
        $this->totalPrice = $totalPrice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $room = Room::find($this->roomId);
        if ($room) {
            $serviceLevel = new UpgradeRoomLevelServices();
            $serviceLevel->sendGift($room, $this->totalPrice);
        }
    }
}

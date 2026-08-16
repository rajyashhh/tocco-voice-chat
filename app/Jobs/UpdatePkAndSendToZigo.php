<?php

namespace App\Jobs;

use App\Classes\Gifts\SendGiftService;
use App\Models\Pk;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class UpdatePkAndSendToZigo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $userId;
    private $receivedIds;
    private $totalPrice;
    private $roomId;
    private $room;


    /**
     * @param array $receivedIds
     * @param float $totalPrice
     * @param string $room
     * @param $userId
     */
    public function __construct($userId, $roomId, array $receivedIds, float $totalPrice, $room)
    {

        $this->receivedIds = $receivedIds;
        $this->totalPrice = $totalPrice;
        $this->room = $room;
        $this->userId = $userId;
        $this->roomId = $roomId;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $lastPk = Room::select(['id'])->where('id', $this->roomId)->first()?->lastPk;
        if (!$lastPk) {
            return;
        }
        (new SendGiftService())->updatePkScoresAndSendToStreamJob($lastPk, $this->userId, $this->roomId, $this->receivedIds, $this->totalPrice, $this->room);
    }


}

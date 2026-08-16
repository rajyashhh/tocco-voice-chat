<?php

namespace App\Classes\Gifts;

use App\Interfaces\RoomJobInterface;
use App\Jobs\TestTestCharizma;
use App\Soalfna\DTO\RoomJob;

class RoomJobFactory
{
    public RoomJobInterface $roomJob;
    public string $type;

    public function setType($type): static
    {
        // Charisma is fully client-side now (owner decision); PK is the only room
        // job still riding this pipe. Any non-pk type is ignored.
        if ($type == 'pk') {
            $this->type = 'pk';
            $this->roomJob = new PKWork();
        }
        return $this;
    }
    public function work($roomJob): array
    {

        return $this->roomJob->work($roomJob);
    }

    public function sendToStream(array $data)
    {
        $data = $this->roomJob->prepareDataToStream($data);
        dispatch(new TestTestCharizma($data, $this->roomJob))->onQueue($this->type . '-job');
    }
}

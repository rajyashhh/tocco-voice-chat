<?php

namespace Modules\HostLevel\Transformers;


use Modules\Events\Transformers\WeeklyStarGift;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHostLevelResource extends JsonResource
{

    public function toArray($request)
    {
        $user = request()->user();
        return [
            'id' => $this->id,
            'picked_level' => $user->hostLevelWinnerByLevelAndEvent($this->id) ? true : false,
            'level' => $this->level,
        ];
    }
}

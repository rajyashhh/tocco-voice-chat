<?php

namespace Modules\HostLevel\Transformers;


use App\Helpers\Common;
use App\Models\GiftLog;
use Modules\HostLevel\Entities\HostLevel;
use Modules\Events\Transformers\WeeklyStarGift;
use Illuminate\Http\Resources\Json\JsonResource;

class HostLevelResource extends JsonResource
{

    public function toArray($request)
    {
        $user = request()->user();
        $diamonds = $request->userDiamonds ?? 0;
        $remaining = $this->diamonds - $diamonds;
        $nextLevel = $request->nextLevel ?? 0;
        $lastLevel = HostLevel::where('level', '>=', $nextLevel)->orderBy('level')->value('level') ?? 0;
       
        return [
            'id' => $this->id,
            'diamond' => $this->diamonds,
            'level' => $this->level,
            'name' => $this->name,
            'img' => $this->img,
            'picked_level' => $user->hostLevelWinnerByLevelAndEvent($this->id) ? true : false,
            'remaining' => $remaining < 0 ? 0 : $remaining,
            'progress' => ($this->level > $lastLevel) ? 0 : $this->progress($this->diamonds, $diamonds),

            'rewards' => WeeklyStarGift::collection($this->whenLoaded('rewards')),
        ];
    }

    public function progress($nextDiamonds, $userDiamonds)
    {
        if ($nextDiamonds <= 0) {
            return 1;
        }

        if ($userDiamonds <= 0) {
            return 0;
        }

        // normalize
        $progress = $userDiamonds / $nextDiamonds;

        // NEW epsilon — anything below 0.0001 becomes zero
        $epsilon = 0.0001;

        if ($progress < $epsilon) {
            return 0;
        }

        if ($progress > 1 - $epsilon) {
            return 1;
        }

        return $progress;
    }
}

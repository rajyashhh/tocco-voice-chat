<?php

namespace  Modules\Vip\Http\Resources;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserVipResource extends JsonResource
{

    public function toArray($request)
    {
        $diff = Carbon::now()->diff(Carbon::createFromTimestamp($this->expire));
        return [
            "target_id" => $this?->id,
            "is_buyed" => $this != null ? true : false,
            "is_used" => ($this != null && $this->is_used == 1 ? true : false),
            "using" => ($this != null && $this->using == 1 ? true : false),
            'expire' => $this->expire != 0 ? date("Y-m-d H:i:s", $this->expire) : 0,
            'remaining_time' => sprintf('%dy %dm %dd %dh %di %ds', $diff->y, $diff->m, $diff->d, $diff->h, $diff->i, $diff->s),
        ];
    }
}

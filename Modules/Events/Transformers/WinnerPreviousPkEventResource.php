<?php

namespace Modules\Events\Transformers;

use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Resources\GiftResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WinnerPreviousPkEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'user_id'   => $this->user?->id,
            'uuid'      => $this->user?->uuid ?? 0,
            'name'      => $this->user?->name ?? '',
            'avatar'    => $this->user?->profile?->avatar ?? '',
            'level'     => $this->level,
            'pk_type'   => $this->pk_type,
            'room_id'   => @$this->room_id ?? 0,
        ];
    }
}

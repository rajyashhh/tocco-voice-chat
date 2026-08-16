<?php

namespace  Modules\Vip\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class VipResource extends JsonResource
{
    public static $prevs = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'exp' => $this->exp,
            'img' => $this->img,

        ];
    }

}

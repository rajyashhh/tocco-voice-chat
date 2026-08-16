<?php

namespace  Modules\Vip\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BackgroundResource extends JsonResource
{

    public function toArray($request)
    {

        return [
            "vip" => $this?->level ?? 0,
            "background" => $this->background_img ?? '',
        ];
    }
}

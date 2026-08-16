<?php

namespace Modules\RoomBoom\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ThemesBoomRoomLevelResource extends JsonResource
{
    public function toArray($request)
    {
        $data =  [

            'level' => $this->level,
            'background' => [
                "type" => $this->image_type_background ?? '',
                "url" => $this->background_image ?? '',
            ],
            'boom' => [
                "type" => $this->image_type_boom ?? '',
                "url" => $this->boom_image ?? '',
            ],

        ];

        return $data;
    }
}

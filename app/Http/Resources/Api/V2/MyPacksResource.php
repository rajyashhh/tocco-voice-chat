<?php

namespace App\Http\Resources\Api\V2;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MyPacksResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'get_type' => $this->get_type_name,
            'type' => $this->type_name,
            'target_id' => $this->target_id,
            'num' => $this->num,
            'expire' => $this->formatted_expire,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at_formatted,
            'updated_at' => $this->updated_at,
            'sender_id' => $this->sender_id,
            'is_used' => (bool) $this->is_used,
            'use_num' => $this->use_num,
            'name' => $this->ware?->name,
            'show_img' => $this->ware?->show_img ?? '',
            'svg' => $this->ware?->img2 ?? '',
            'price' => $this->price ?? '',
            'price_item' => $this->price ?? '',
            'image_type' => $this->ware?->image_type ?? '',
            'is_dress' => $this->is_dress,
            'title' => $this->title_text,
            'color' => $this->resolved_color,
            'using' => $this->using,
        ];
    }

}

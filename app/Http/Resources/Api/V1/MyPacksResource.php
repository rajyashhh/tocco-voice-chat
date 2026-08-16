<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MyPacksResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'get_type' => $this->get_type,
            'type' => $this->type,
            'target_id' => $this->target_id,
            'num' => $this->num,
            'expire' => $this->expire,
            'is_read' => $this->is_read,
            'created_at' => Carbon::parse($this->created_at)->setTimezone($request->hasHeader('tz') ? $request->header()['tz'][0] : 'UTC')->format('Y-m-d H:i:s') ??'',
            'updated_at' => $this->updated_at,
            'sender_id' => $this->sender_id,
            'is_used' => $this->is_used == 1 ? true : false,
            'use_num' => $this->use_num,
            'name' => $this->name,
            'show_img' => $this->show_img,
            'price' => @$this->price ?? '',
            'is_dress' => $this->is_dress,
            'title' => $this->title,
            'color' => $this->color,
        ];
    }


}

<?php

namespace Modules\LuckyBox\Http\Resources;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class BoxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $is_label = false;
        if ($this->type == 1 && $this->has_label == 1) {
            $is_label = true;
        }
        $dynamic_users_values = explode(',', $this->dynamic_users_values);
        $dynamic_users_values = array_map('trim', $dynamic_users_values);
        return [
            'id' => $this->id,
            'type' => $this->type == 1 ? 'super' : 'normal',
            'coins' => $this->coins,
            'users_num' => $this->type == 1 ? $this->users : $dynamic_users_values,
            'image' => $this->image,
            'is_label' => $is_label,
            'time'     => $this->type == 0 ? Common::getConf('normal_box_duration') : $this->duration,
        ];
    }
}

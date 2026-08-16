<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AllGameInRoomResource extends JsonResource
{
    public function toArray($request)
    {
        $type = \request()->type ?? 0;

        return [
            'id'      =>  $this->id,
            'custom_id' => (string) ($this->custom_id ?? ''),
            'name'      => (auth()->user()->lan == "ar" ? $this->name : $this->name_en),
            'image'     => @$this->image ?? '',
            'url' => $this->in_room == 1
                ? ($this->url ?? '')
                : ($this->in_room == 0
                    ? ($this->mini_url ?? '')
                    : ($this->hd_url ?? '')
                ),
            'webView_config' => $this->type == 2 ? true : false,
            'high_safety' => (intval(@$this->height_image) ?? 0),
            'high' => intval($this->in_room == 1 ? (floatval($this->height ?? 0)) : null),
            'in_room' => @$this->in_room,
            'is_hot' => 0,
            // UTD (type=4) is launched exactly like Quantum (type=3) — same company,
            // same webhook/url-games flow. The app only knows type=3, so present UTD
            // as type=3 so a tap opens the game. See AllGameResource for details.
            'type' => (($this->type ?? 0) == 4) ? 3 : ($this->type ?? 0),
        ];
    }
}

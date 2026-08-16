<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AllGameResource extends JsonResource
{
    public function toArray($request)
    {
        $type = \request()->type ?? 0;
        
        return [
            'id'      =>  $this->id,
            'custom_id' => (string) ($this->custom_id ?? ''),
            'name'      => (auth()->user()->lan == "ar" ? $this->name : $this->name_en),
            'image'     => @$this->image ?? '',
            'url'       => $type == 1 ? (@$this->mini_url ?? '') : (@$this->url ?? ''),
            'webView_config' => $this->type == 2 ? true : false,
            'high_safety' => (intval(@$this->height_image) ?? 0),
            'high' => intval($this->in_room == 1 ? (floatval($this->height ?? 0)) : null),
            'in_room' => @$this->in_room,
            'is_hot' => 0,
            // UTD (type=4) brokers the SAME game company as Quantum (type=3) on the
            // identical launch flow (webhook/url-games resolves both, see
            // NewLeaderCCGameController::urlGames whereIn([3,4])). The mobile app
            // only knows how to open type=3, so we surface UTD games as type=3 —
            // the app fires the exact same launch call and the game opens. No app
            // update or per-game retyping needed.
            'type' => (($this->type ?? 0) == 4) ? 3 : ($this->type ?? 0),
        ];
    }
}

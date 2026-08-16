<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowUserSettingResource extends JsonResource
{
    public function toArray($request)
    {
        // No settings row yet (new user / un-backfilled): default every effect ON.
        // Only an EXPLICIT 0 disables an effect. Defaulting a missing row to false
        // silently hid all gift animations and banners for those users.
        if ($this->resource === null) {
            return [
                'id'         => null,
                'user_id'    => null,
                'show_git'   => true,
                'show_intro' => true,
                'show_banner' => true,
            ];
        }

        return [
            'id'    => $this->id,
            'user_id' => $this->user_id,
            'show_git' => ((int) ($this->show_git ?? 1)) !== 0,
            'show_intro' => ((int) ($this->show_intro ?? 1)) !== 0,
            'show_banner' => ((int) ($this->show_banner ?? 1)) !== 0,
        ];
    }
}

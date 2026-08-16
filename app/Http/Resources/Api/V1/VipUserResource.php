<?php

namespace App\Http\Resources\Api\V1;

use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Resources\Json\JsonResource;

class VipUserResource extends JsonResource
{

    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'level' => $this->level ?? 0,
            'qty' => $this->qty,
            'expire' => $this->expire,
        ];
    }
}

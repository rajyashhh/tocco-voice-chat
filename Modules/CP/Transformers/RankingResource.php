<?php

namespace Modules\CP\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CP\Entities\CpLevel;

class RankingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'level'         => [
                'id' => $this->level_id,
                'img' => $this->level?->img
            ],
            'exp'           => (int)$this->total_gifts,
            "userOne"       => [
                "id"        => $this->fromUser?->id,
                "uid"       => $this->fromUser?->uuid,
                "name"      => $this->fromUser?->name,
                "image"     => $this->fromUser?->profile?->avatar,
                "gender"    => $this->fromUser?->profile?->gender,

            ],
            "userTwo"      => [
                "id"        => $this->toUser?->id,
                "uid"       => $this->toUser?->uuid,
                "name"      => $this->toUser?->name,
                "image"     => $this->toUser?->profile?->avatar,
                "gender"    => $this->toUser?->profile?->gender,
            ]
        ];
    }
}

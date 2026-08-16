<?php

namespace Modules\CP\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CP\Entities\CpLevel;

class TopRankingResource extends JsonResource
{
    public function toArray($request)
    {
        $cp = $this->cp;
        return [
            'id'            => $cp->id,
            'level'         => [
                'id' => $cp->level_id ?? 0,
                'img' => $cp->level?->img ?? '',
            ],
            'exp'           => $this->total_gifts,
            "userOne"       => [
                "id"        => $cp->fromUser?->id ?? 0,
                "uid"       => $cp->fromUser?->uuid ?? '',
                "name"      => $cp->fromUser?->name ?? '',
                "image"     => $cp->fromUser?->profile?->avatar ?? '',
                "gender"    => $cp->fromUser?->profile?->gender ?? 0,

            ],
            "userTwo"      => [
                "id"        => $cp->toUser?->id ?? 0,
                "uid"       => $cp->toUser?->uuid ?? '',
                "name"      => $cp->toUser?->name ?? '',
                "image"     => $cp->toUser?->profile?->avatar ?? '',
                "gender"    => $cp->toUser?->profile?->gender ?? '',
            ]
        ];
    }
}
<?php

namespace Modules\CP\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestCpResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'id'        => $this->id,
            "relation"  =>[
                "id"    => $this->relation?->id,
                "title" => $this->relation?->title,
                "image" => $this->relation?->image,
                "price" => $this->relation?->price,
            ],
            "from_user" => [
                "id"    => $this->fromUser?->id,
                "image" => $this->fromUser?->profile?->avatar,
                "name"  => $this->fromUser?->name,
            ],
            "to_user"   =>[
                "id"    => $this->toUser?->id,
                "image" => $this->toUser?->profile?->avatar,
                "name"  => $this->toUser?->name,
            ],
            "created_at" => $this->created_at
        ];
    }
}

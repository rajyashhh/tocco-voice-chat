<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Moment\Entities\MomentCommint;
use Modules\Moment\Entities\MomentLikes;

class MomentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'description' => $this->description,
            'comment_num' => $this->comments->count(),
            'like_num' => $this->likes->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'img' => $this->img,
            'user'=> $this->user,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Reals\Entities\RealUserComment;
use Modules\Reals\Entities\RealUserLike;

class RealResource extends JsonResource
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
            'url' => $this->url,
            'comment_num' => $this->comments->count(),
            'like_num' => $this->likes->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sub_video' => $this->sub_video,
            'intro_image' => $this->intro_image,
            'user' => $this->user ? [
                'uuid' => $this->user->uuid,
                'name' => $this->user->name,
            ] : (object)[],

        ];
    }
}

<?php

namespace Modules\Moment\Transformers;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                 => $this->id ?? 0,
            'user_id'            => $this->user_id ?? 0,
            'description'        => $this->description ?? '',
            'comment_num'        => (int) $this->comments_count ?? 0, // Assuming you have a relationship for comments
            'like_num'           => (int) $this->likes_count ?? 0, // Assuming you have a relationship for likes
            'gifts_count'        => (int)$this->gifts?->sum('gifts_count') ?? 0,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at ?? '',
            'img'                => $this->img ?? '',
            'is_like'            => @$this->likes_exists ?? false,
            'images'             => @$this->images,
            'user'               => @$this->whenLoaded('user') ?  new UserResource($this->whenLoaded('user')) : [],
            // 'user' => optional($this->user??0)->relationLoaded('user') ? new UserResource($this->user) : [],

        ];
    }
}

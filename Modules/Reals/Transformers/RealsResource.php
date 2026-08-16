<?php

namespace Modules\Reals\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'description'    => $this->description,
            'url'            => $this->url,
            'status'         => $this->status ?? 'ready',
            'duration'       => $this->duration !== null ? (float) $this->duration : null,
            'sub_video'      => $this->sub_video,
            'thumbnail'      => $this->thumbnail,
            // Legacy key kept for old app builds; new thumbnails land on the same path.
            'sub_frame'      => $this->thumbnail ?: ((config('app.env') != 'production' ? '' : 'test-') . "frames/" . $this->id . '.jpg'),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'likes_count'    => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'views_count'    => $this->views_count ?? 0,
            'share_count'    => $this->share_num ?? 0,
            // Use pre-loaded withExists result — avoids N+1 query per reel
            'likes_exists'   => (bool) ($this->likes_exists ?? false),
            'user'           => new UserResource($this->user),
        ];
    }
}

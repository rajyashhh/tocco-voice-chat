<?php

namespace Modules\Moment\Transformers;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentDashboardResource extends JsonResource
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
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'description'  => $this->description,
            'comment_num'  => $this->comments->count(),
            'like_num'     => $this->likes->count(),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'img'          => $this->img,
            'comments'     => $this->comments->map(fn ($comment) => [
                'id'          => $comment->id,
                'moment_id'   => $comment->moment_id,
                'user_id'     => $comment->user_id,
                'comment'     => $comment->comment,
                'created_at'  => $comment->created_at,
                'updated_at'  => $comment->updated_at,
            ]),
            'likes'        => $this->likes->map(fn ($like) => [
                'id'          => $like->id,
                'moment_id'   => $like->moment_id,
                'user_id'     => $like->user_id,
                'created_at'  => $like->created_at,
                'updated_at'  => $like->updated_at,
            ]),

            'gifts' => $this->gifts->map(fn ($gift) => [
                'gifts_count'  => $gift->gifts_count,
            ]),
            // 'gifts'        => $this->gifts->map(fn ($gift) => [
            //     'id'                 => $gift->id,
            //     'name'               => $gift->name,
            //     'e_name'             => $gift->e_name,
            //     'type'               => $gift->type,
            //     'vip_level'          => $gift->vip_level,
            //     'hot'                => $gift->hot,
            //     'is_play'            => $gift->is_play,
            //     'price'              => $gift->price,
            //     'img'                => $gift->img,
            //     'show_img'           => $gift->show_img,
            //     'show_img2'          => $gift->show_img2,
            //     'sort'               => $gift->sort,
            //     'enable'             => $gift->enable,
            //     'created_at'         => $gift->created_at,
            //     'updated_at'         => $gift->updated_at,
            //     'music_gift'         => $gift->music_gift,
            //     'international_gift' => $gift->international_gift,
            //     'use_count'          => $gift->use_count,
            //     'image_type'         => $gift->image_type,
            //     'pivot'              => $gift->pivot ? [
            //         'moment_id' => $gift->pivot->moment_id,
            //         'gift_id'   => $gift->pivot->gift_id,
            //     ] : null,
            // ]),
        ];
    }


}

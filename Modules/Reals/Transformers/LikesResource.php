<?php

namespace Modules\Reals\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LikesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        return [
             'id' => $this->id,
            'real_id' => $this->real_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),

   
        ];
       }
}

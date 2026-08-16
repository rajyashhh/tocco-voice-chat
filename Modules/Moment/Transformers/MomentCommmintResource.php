<?php

namespace Modules\Moment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MomentCommmintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return[
        'id' => $this->id,
        'moment_id' => $this->moment_id,
        'user_id' => $this->user_id,
        'comment' => $this->comment,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,  
        'user' => new UserResource($this->whenLoaded('user')),

        
    ] ;
 }
}

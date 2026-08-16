<?php

namespace Modules\TaskStream\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskStreamResource extends JsonResource
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
            'task_id' => $this->id,
            'main_room_id' => $this->room_id,
            'rooms' => $this->when($this->whenLoaded('rooms'), $this->rooms->pluck('room_id')),
            'created_at' => $this->created_at,
            'updated_at' => $this->when(
                $this->whenLoaded('rooms'),
                $this->rooms->sortByDesc('updated_at')->first()?->updated_at,
            ),
            'status' => 'updated',
        ];
    }
}

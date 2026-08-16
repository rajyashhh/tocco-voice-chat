<?php

namespace Modules\TaskStream\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PkSessionResource extends JsonResource
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
            'id' => $this->id,
            'status' => $this->status,
            'team_1' => $this->whenHas('team_1', explode(',', $this->team_1)),
            'team_2' => $this->whenHas('team_2', explode(',', $this->team_2)),
            'duration' => $this->whenHas('duration'),
            'started_at' => $this->whenHas('created_at'),
            'ends_at' => $this->whenHas('ends_at'),

            "winner_team" => $this->whenHas('winner'),
            "team_1_score" => $this->whenHas('team_1_score'),
            "team_2_score" => $this->whenHas('team_2_score'),

            'participants' => $this->whenHas('participants_data'),
        ];
    }
}

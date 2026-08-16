<?php


namespace Modules\Moment\Transformers;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentGiftUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $data = [
            'id'   => $this->user?->id,
            'uuid' => $this->user?->uuid,
            'name' => $this->user?->name ?: '',
            'image' => $this->user?->profile?->avatar ?? '',
            'level' => Common::level_center(@$this->user),
             'total_num_gift'=> (int)$this->num,

        ];
        return $data;
    }
}
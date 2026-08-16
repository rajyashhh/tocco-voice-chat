<?php

namespace Modules\SpecialId\Transformers;



use Illuminate\Http\Resources\Json\JsonResource;

class SpecialUsersRecourse extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'  => $this->id,
            'name' => $this->user->name,
            'uuid' => $this->user->uuid,
            'ware' =>$this->ware->value,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;



use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawFieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $name = app()->getLocale() === 'ar' ? ($this->name ?? $this->name_en) : ($this->name_en ?? $this->name);
       
        return [
            'id' => $this->id,
            'name' => $name?? '',
            'type' => $this->type,
            'validate' => $this->validate,
        ];
    }
    }

<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class FamilyRankResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {


        return [
            'id'        => @$this->family?->id ?? 0,
            'name'      => @$this->family?->name ?? '',
            'introduce' => @$this->family?->introduce ?? '',
            'image'     => @$this->family?->image ?? '',
            'rank'      => numToString(@$this->coins ?? 0) ,
            'country'=> [
                'id' =>@$this->family?->owner->country->id ?? 0,
                'name' => @$this->family?->owner->country->name ?? '',
                'flag' => @$this->family?->owner->country->flag ?? '',
            ],
            'level'=>@$this->family->level?:'',
            

        ];
    }
}

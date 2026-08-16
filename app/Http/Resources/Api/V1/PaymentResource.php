<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
       $user = \Auth::user();
       $payments=$user->paymentGateways->pluck("id")->toArray();
        return [
            'id'            =>$this->id,
            'title'         =>$this->title,
            'photo'         =>$this->photo,
            'is_selected'   =>in_array($this->id,$payments),
        ];
    }
}

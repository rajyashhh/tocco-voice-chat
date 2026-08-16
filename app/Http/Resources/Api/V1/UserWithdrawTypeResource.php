<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;



use Illuminate\Http\Resources\Json\JsonResource;

class UserWithdrawTypeResource extends JsonResource
{
 
    public function toArray($request)
    {
        $name = app()->getLocale() === 'ar' ? $this->payment_withdraw_type->name : $this->payment_withdraw_type->name_en;
        return [
            'id' => $this->payment_withdraw_type?->id,
            'name' => $name ?? '',
            'image' => $this->payment_withdraw_type->image,
            'min_value' => $this->payment_withdraw_type->min_value,
            // "fields"=>getUserWithdrawFieldsResource::collection($this->payment_withdraw_type->userWithdrawFields),
        ];
    }
}

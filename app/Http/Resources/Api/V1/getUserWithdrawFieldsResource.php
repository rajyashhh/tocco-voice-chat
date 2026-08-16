<?php

namespace App\Http\Resources\Api\V1;



use Illuminate\Http\Resources\Json\JsonResource;

class getUserWithdrawFieldsResource extends JsonResource
{
 
    public function toArray($request)
    {
        $name = app()->getLocale() === 'ar' ? $this->payment_withdraw_field->name : $this->payment_withdraw_field->name_en;
        return [
            'id' => $this->id,
            'field_name' => $name ?? '',
            'value' => $this->value,
        ];
    }
}

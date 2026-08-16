<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\UserPaymentWithdraw;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class WithdrawTypeResource extends JsonResource
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
        $userPayments = UserPaymentWithdraw::where("user_id",Auth::id())->pluck("payment_withdraw_type_id")->toArray();
        return [
            'id' => $this->id,
            'name' => $name ?? '',
            'image' => $this->image,
            'min_value' => $this->min_value,
            'exchange_rate' => $this->exchange_rate,
            'fields' => $this->withdrawFields?->map(fn ($f) => [
                'id'       => $f->id,
                'name'     => app()->getLocale() === 'ar' ? ($f->name ?? $f->name_en) : ($f->name_en ?? $f->name),
                'type'     => $f->type,
                'validate' => $f->validate,
            ])->values(),
            'is_conected'=>in_array($this->id,$userPayments)
        ];
    }
}

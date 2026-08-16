<?php

namespace Modules\UsersWallet\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletFieldResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'type' => $this->type,
            'is_required' => (bool) $this->is_required,
            'order' => $this->order,
        ];
    }
}

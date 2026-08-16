<?php

namespace App\Http\Resources;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'type'              => $this->type,
            'message_key'       => $this->message,
            'message'           => Common::renderWalletMessage($this->message ?? '', $this->description_data),
            'value'             => $this->value,
            'description_raw'   => $this->description,
            'description_data'  => json_decode($this->description_data, true),
            'transactions_type' => $this->transactions_type,
            'created_at'        => $this->created_at,
        ];
    }
}

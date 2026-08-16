<?php

namespace App\Http\Resources\Dashboard\Charges;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardChargesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sender =[
            'id' => @$this->sender->id ?? 0,
            'name' => @$this->sender->name ?? '',
            'img' => @$this->sender->profile->avatar ?? '',
        ];
        $receiver =[
            'id' => @$this->receiver->id ?? 0,
            'name' => @$this->receiver->name ?? '',
            'img' => @$this->sender->profile->avatar ?? '',
        ];

        return [
            'id' => $this->id,
            'sender' => $sender,
            'receiver' => $receiver,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_before + $this->amount,
            'amount' => $this->amount,
            'date' => $this->created_at,
        ];
    }
}

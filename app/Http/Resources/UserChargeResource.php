<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $admin =   Admin::where('id', $this->charger_id)->first();
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'usd' => $this->usd,
            'charger_type' => $this->charger_type,
            'charger' =>  $this->charger_type != 'dash' ?
                [
                    'id' => $this->sender->id ?? 0,
                    'name' => $this->sender->name ?? '',
                    'uuid' => $this->sender->uuid ?? 0,
                    'image' => $this->sender->profile?->avatar ?? '',
                ] : [
                    'id' => $admin->id ?? 0,
                    'name' => $admin->name ?? '',
                    'image' => $admin->avatar ?? '',

                ],
        ];
    }
}

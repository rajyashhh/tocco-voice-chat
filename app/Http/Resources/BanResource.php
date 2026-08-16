<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\BanType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $banType = BanType::find($this->ban_type_id);
        $name_ar = $banType->name_ar ?? '';
        $name_en = $banType->name_en ?? '';
        return [
            'uid' => $this->uid,
            'duration' => $this->duration,
            'type' => $this->type,
            'reason' => $this->description_ar,
            'ban_type' => [
                'ban_type_id' => $this->ban_type_id,
                'name_ar' => $name_ar,
                'name_en' => $name_en,
            ],
            'image' => $this->img,
            'device_number' => $this->device_number,
            'staff_id' => $this->staff_id,
            'created_at' => Carbon::createFromTimestamp(strtotime($this->created_at))
                ->timezone(auth()->user()->time_zone ?? 'UTC')
                ->format("Y-m-d h:i A"),
        ];

    }
}

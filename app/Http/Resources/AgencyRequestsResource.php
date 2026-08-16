<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AgencyRequestsResource extends JsonResource
{
    public function toArray($request)
    {
        $type = \request()->type ?? 0;
        return [
            'id'      =>  $this->id,
            'name'      => $this->name ?? '',
            'whatsapp' => $this->phone ?? '',
            'img' => $this->img ?? '',
            'owner' => [
                'id' => $this->owner->id ?? 0,
                'name' => $this->owner->name ?? '',
                'uuid' => $this->owner->uuid ?? 0,
            ],
            'additional_info' => [
                'country'  => $this->additionalInfo->country ?? '',
                'gmail' => $this->additionalInfo->gmail ?? '',
                'video' => $this->additionalInfo->video ?? '',
                'face_image_nationalId' => $this->additionalInfo->face_image_nationalId ?? '',
                'back_image_nationalId' => $this->additionalInfo->back_image_nationalId ?? '',
                'salary' => $this->additionalInfo->salary ?? 0,
                'host' => $this->additionalInfo->host ?? 0,
                'user_id' => $this->additionalInfo->user_id ?? 0,
                'history_app_info' => $this->additionalInfo->history_app_info ?? '',
            ]

        ];
    }
}

<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgenciesRequestsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_name' => $this->owner->name,
            'owner_uuid' => $this->owner->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'additionalInfo_country' => $this->additionalInfo?->country,
            'img' => $this->img,
            'additionalInfo_gmail' => $this->additionalInfo?->gmail,
            'additionalInfo_video' => StorageHelper::url($this->additionalInfo?->video),
            'additionalInfo_face_image_nationalId' =>  $this->additionalInfo?->face_image_nationalId ? StorageHelper::url($this->additionalInfo?->face_image_nationalId) : 'No image founded',
            'additionalInfo_back_image_nationalId' => $this->additionalInfo?->back_image_nationalId? StorageHelper::url($this->additionalInfo?->back_image_nationalId) : 'No image founded',
            'additionalInfo_salary' => $this->additionalInfo?->salary,
            'addtionalInfo_host' => $this->additionalInfo?->host,
            'additionalInfo_user_id' => $this->additionalInfo?->user_id,
            'additionalInfo_app_info' => $this->additionalInfo?->history_app_info
        ];
    }
}

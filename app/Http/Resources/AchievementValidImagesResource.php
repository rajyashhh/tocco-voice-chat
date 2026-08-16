<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementValidImagesResource extends JsonResource
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
            'image' => $this->image ?? '',
            'type' => $this->type,
            'created_at' => Carbon::parse($this->created_at)->toIso8601String(),
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
            'user_id' => $this->user_id,
            'file' => $this->file ? asset($this->file) : '', // Ensure file is a full URL
        ];
    }
}

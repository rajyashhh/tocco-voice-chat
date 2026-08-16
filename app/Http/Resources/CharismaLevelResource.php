<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharismaLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {


        return [
            'level' => $this->level,
            'points' => $this->points,
            'image' =>  $this->image,

        ];
    }
}

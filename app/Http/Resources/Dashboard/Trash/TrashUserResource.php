<?php

namespace App\Http\Resources\Dashboard\Trash;
use App\Helpers\StorageHelper;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrashUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'id'   =>$this->id  ?? '',
            'deleted_at'   =>$this->deleted_at ,
            'name' =>$this->name ?? '',
            'img'  =>$this->profile->avatar ?? null,
        ];
    }
}

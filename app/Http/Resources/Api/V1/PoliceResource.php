<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Police;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PoliceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $title = app()->getLocale() === 'ar' ? ($this->title ?? $this->title_en) : ($this->title_en ?? $this->title);
        $body = app()->getLocale() === 'ar' ? ($this->body ?? $this->body_en) : ($this->body_en ?? $this->body);
        return [
            'id' => $this->id,
            'title' => $title ?? '',
            'body' => $body ?? "",
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    }

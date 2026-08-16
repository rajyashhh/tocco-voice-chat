<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AllGamesResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      =>  $this->id,
            'name'      =>  (auth()->user()->lan == "ar" ? $this->name : $this->name_en)
        ];
    }
}

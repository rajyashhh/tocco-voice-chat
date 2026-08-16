<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Http\Resources\CountryResource;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Country;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\Pack;
use App\Models\Room;
use App\Models\Ware;
use Carbon\Carbon;
use http\Client\Curl\User;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralUserWareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $data = [
            'id'   => $this->id ?? 0,
            'image' => $this->img2 ?? '',
            'image_type' => $this->image_type ?? 'svga',
            'key' => $this->key ?? '',

        ];

        return $data;
    }
}

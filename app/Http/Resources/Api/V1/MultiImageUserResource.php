<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

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

class MultiImageUserResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => @$this->id, // both
            'img' => @$this->img ?? '',
        ];

        return $data;
    }
}

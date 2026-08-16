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
use Modules\Vip\Entities\Vip;
use App\Models\Ware;
use Carbon\Carbon;
use http\Client\Curl\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MembersUserResource extends JsonResource
{
    private $familyId;

    public function __construct($resource, $familyId = null)
    {
        parent::__construct($resource);
        $this->familyId = $familyId;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => @$this->id,
            'family_id' => (int)$this->familyId ? (string)$this->familyId: (string)$request->family_id, // Include family_id in the response
            'name' => @$this->name ?: '',
            'profile' => [
                'image' => $this->profile?->avatar,
                'age' => Carbon::parse($this->profile?->birthday)->age,
                'gender' => (int)($this->profile?->gender ?? 1),
            ],
            'type_user'            => intval(@$this->type_user) ?: 0, // both
            'vip_level' => $this->userVip->level ?? 0,
            "manger_type"          => new MangerTypeResource(@$this->mangerType),
            'uuid'                 => @$this->uuid, // both
            'monthly_diamond_received' => $this->monthly_diamond_received,
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            $this->mergeWhen(isset(request()->family_status), [
                'family_status' => request()->family_status
            ]),
            /* 'level'=> [
                'receiver_img' => @$this->getImageReceiverOrSender('receiver_id',1)->img,
                'sender_img' => @$this->getImageReceiverOrSender('sender_id',2)->img,
            ],*/
            'frame_id' => @$this->dress_1,
            'frame' => Common::getUserDress(@$this->id, @$this->dress_1, 4, 'img2', true) ?: Common::getUserDress(@$this->id, @$this->dress_1, 4, 'img1', true),
            'is_family_admin'=> @$this->is_family_admin,
        ];

        return $data;
    }
}

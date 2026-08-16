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

class GroupChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        // $frame  =
        //     (@$this->user  && $this->user->dress_1) ? Common::getUserDress($this->user->id, $this->user->dress_1, 4, 'img2', true) ?: Common::getUserDress($this->user->id, $this->user->dress_1, 4, 'img1', true) : '';
        $framePack = $this->user?->packs
            ->firstWhere(fn($p) => $p->type == 4 && $p->target_id == $this->user->dress_1);

        $frame = $framePack?->ware?->img2 ?? $framePack?->ware?->img1 ?? '';
        $data = [
            'id' => (int)(@$this->user?->id ?? 0),
            'uuid' => @$this->user?->uuid ?? '',
            'name' => @$this->user?->name ?? '',
            'profile' => [
                'image' => $this->user?->profile?->avatar ?? '',
                // 'age' => Carbon::parse(@$this->user->profile->birthday)->age,
                 'gender' => $this->user?->gender ?? 0,
            ],
            'frame' => $frame,
            'frame_id' => $frame != '' ? (@$this->user->dress_1 ?? 0) : 0,
            'vip' => [
                'level' => @$this->user?->UserVip?->level ?? 0,
            ],
            'level' => [
                'receiver_img' => $this->user?->receiverLevel?->img ?? '',
                'sender_img'   => $this->user?->senderLevel?->img ?? '',
            ],
            'has_color_name' => $this->user->hasPackOfType(18),
            //Common::hasInPack(@$this->user->id, 18),
            'message_id' => @$this->id,
            'group_message' => @$this->text ?? '',
            'group_image' => $this->image ?? '',
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
            'replay' => $this->parent ? new ReplayGroupChatResource(@$this->parent) : null,
        ];

        return $data;
    }
}

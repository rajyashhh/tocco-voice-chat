<?php

namespace Modules\SalaryTransaction\Transformers;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Helpers\UserPackHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AdminsAgencyResource;
use App\Http\Resources\Api\V1\AgencyJoinReqResource;
use App\Http\Resources\Api\V1\ReceiverGiftLogResource;
use App\Models\AgencyJoinRequest;

class FilterAgancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $type = '';
        // if (($this->Shipping_agency == 1) && ($this->Host_agency == 1)) {
        //     $type = 'hosts and shipping';
        // } elseif (($this->Shipping_agency == 0) && ($this->Host_agency == 1)) {
        //     $type = 'hosts';
        // } elseif (($this->Shipping_agency == 1) && ($this->Host_agency == 0)) {
        //     $type = 'shipping';
        // }
        if ($this->type == 1) {
            $type = 'hosts ';
        } elseif ($this->type == 2) {
            $type = 'shipping';
        }

        $giftLog = $this->giftLogs->filter(fn($log) => $log->receiver)->groupBy('receiver_id')
            ->map(function ($logs) {
                return [
                    'receiver' => $logs->first()->receiver,
                    'exp' => $logs->sum('giftPrice')
                ];
            })
            ->sortByDesc('exp')
            ->take(5)
            ->values();

        return [
            'id' => $this->id,
            'name' => @$this->name,
            'image' => @$this->img,
            'total_members' => $this->mempers->count(),
            'members' => AgencyMemberResource::collection($this->mempers),
            'agency_type' =>  $type,
            'is_join_request' => $this->joinRequests->where('user_id', request()->user()->id)->isNotEmpty(),
            'owner' => [
                'id' => $this->owner->id ?? 0,
                'uuid' => $this->owner->uuid ?? '',
                'name' => @$this->owner->name ?? '',
                'image' => @$this->owner->profile?->avatar ?? '',
                'frame' => UserPackHelper::getFrameImage($this->owner),
                'frame_type' => UserPackHelper::getFrameType($this->owner),
            ],
            'admins' => AdminsAgencyResource::collection($this->admins),
            'star' => ReceiverGiftLogResource::collection($giftLog),
            'bio'               => $this->contents,
        ];
    }
}

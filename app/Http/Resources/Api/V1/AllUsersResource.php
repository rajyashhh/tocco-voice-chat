<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use App\Models\User;
use App\Facades\UserHandling;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Http\Services\UserAchievementService;

class AllUsersResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $usersSameDeviceToken =  User::select("name", 'uuid', 'phone')->where('device_token', $this->device_token)->where('id', '!=', $this->id)->where('device_token', '!=', null)->get();
        $achievement      = new UserAchievementService();
        $data_achivement = $achievement->getUserAchievement($this->resource);
        return [
            'id' => $this->id,
            'coins' => number_format($this->di),
            'uuid' => $this->uuid ?? 0,
            'original_uuid' => $this->original_uuid ?? 0,
            'name' => $this->name ?? '',
            'nickname' => $this->nickname ?? '',
            'charge_status' => $this->charge_status == true ? 1 : 0,
            'hide_chat' => @$this->userSetting->hide_chat ?? 0,
            'show_invite_code' => @$this->userSetting->show_invite_code,
            'transfer_salary' => $this->transfer_salary  == true ? 1 : 0,
            'reals_count' =>  count($this->reals),
            'moment_count' => count($this->moments),
            'total_days' => $this->total_days,
            'total_hours' => $this->liveTime->sum("hours"),
            'image' => $this->profile?->avatar ?? '',
            'image_id' => $this->profile?->image_id ?? '',
            'can_play' => UserHandling::chickLevelToPlay($this->resource),
            'phone' => $this->phone ?? '',
            'agency_id' => $this->agency_id ?? 0,
            'family_id' => $this->family_id ?? 0,
            'agency' => [
                'id' => @$this?->agency?->id ?? 0,
                'name' => @$this?->agency?->name ?? '',
                'notice' => @$this->agency->notice ?? '',
                'phone' => @$this?->agency?->phone ?? '',
                'url' => @$this->agency?->url ?? '',
                'image' => @$this?->agency?->img ?? '',
                'contents' => @$this?->agency?->contents ?? '',
            ],
            'target' => $this->targets()->orderBy('created_at', 'desc')->get()->map(function ($target) {
                return [
                    'id'                  => $target->id,
                    'add_month'           => $target->add_month,
                    'add_year'            => $target->add_year,
                    'target_usd'          => $target->target_usd,
                    'target_hours'        => $target->target_hours,
                    'target_days'         => $target->target_days,
                    'target_agency_share' => $target->target_agency_share,
                    'user_diamonds'       => $target->user_diamonds,
                    'user_hours'          => $target->user_hours,
                    'user_days'           => $target->user_days,
                    'user_obtain'         => $target->user_obtain,
                    'agency_obtain'       => $target->agency_obtain,
                    'updated_at'          => Carbon::parse($target->updated_at)->toDateTimeString(), // Format date
                ];
            }),
            'device-token' => [
               'count' => User::where('device_token', $this->device_token)->count(),
                'users' => $usersSameDeviceToken->map(function ($user) {
                    return [
                        'name'     => $user['name'] ?? '',
                        'uuid' => $user['uuid'] ?? 0,
                        'phone'  => $user['phone'] ?? 0,
                    ];
                }),
            ],
            'achievements' => $data_achivement->map(function ($user) {

                $img = $user["valid_image"] ?? $user["custom_image"];
                return [
                    'id'     => $user['id'] ?? 0,
                    'target' => $user['target'] ?? '',
                    'image'  => $img ?? '',
                ];
            }),


        ];
    }
}

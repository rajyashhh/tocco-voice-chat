<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyDetailsResource extends JsonResource
{
    /**
     * Pre-computed gift aggregation data injected by the controller.
     * Shape: ['stars' => Collection, 'heroes' => Collection, 'admins' => Collection]
     */
    private array $giftData;

    public function __construct($resource, array $giftData = [])
    {
        parent::__construct($resource);
        $this->giftData = $giftData;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user      = $request->user();
        $adminUser = $user?->agencyUserJob;

        $giftLog     = $this->giftData['stars']  ?? collect();
        $heroGiftLog = $this->giftData['heroes'] ?? collect();
        $admin       = $this->giftData['admins'] ?? collect();

        return [
            'id'               => $this->id ?: 0,
            'name'             => $this->name ?: '',
            'img'              => $this->img ?: '',
            'bio'              => $this->contents,
            'owner'            => new MyDataForAgancyResource($this->owner) ?: [
                'id'         => 0,
                'uuid'       => '',
                'target_usd' => 0,
                'name'       => '',
                'profile'    => ['image' => ''],
            ],
            'user_agency_status' => $this->app_owner_id == $user->id ? 2 : ($adminUser ? 1 : 3),
            'admins'             => AdminsAgencyResource::collection($admin),
            'star'               => ReceiverGiftLogResource::collection($giftLog),
            'heroes'             => SenderGiftLogResource::collection($heroGiftLog),
        ];
    }
}

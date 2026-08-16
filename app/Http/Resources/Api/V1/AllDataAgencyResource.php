<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class AllDataAgencyResource extends JsonResource
{
    /**
     * Pre-computed gift aggregation data injected by the controller.
     * Shape: ['target' => float, 'stars' => Collection, 'heroes' => Collection]
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
        $authUser = Auth::user();
        $owner    = @$authUser->ownAgency;
        $admin    = @$authUser->agencyUserJob;

        $type = '';
        if ($this->type == 1) {
            $type = 'hosts ';
        } elseif ($this->type == 2) {
            $type = 'shipping';
        }

        $result      = $this->giftData['target'] ?? 0;
        $giftLog     = $this->giftData['stars']  ?? collect();
        $heroGiftLog = $this->giftData['heroes'] ?? collect();

        return [
            'id'                 => $this->id ?: 0,
            'target'             => $result ?: 0,
            'name'               => $this->name ?: '',
            'notice'             => $this->notice ?: '',
            'phone'              => $this->phone ?: 0,
            'img'                => $this->img ?: '',
            'agency_type'        => $type,
            'num_of_hosts'       => $this->mempers_count ?? 0,
            'owner'              => new MyDataForAgancyResource($this->owner) ?: [
                'id'         => 0,
                'uuid'       => '',
                'target_usd' => 0,
                'name'       => '',
                'profile'    => ['image' => ''],
            ],
            'mempers_count'      => $this->mempers_count,
            'user_agency_status' => $owner ? 2 : ($admin ? 1 : 3),
            'admins'             => AdminsAgencyResource::collection($this->admins),
            'heroes'             => ReceiverGiftLogResource::collection($giftLog),
            'star'               => SenderGiftLogResource::collection($heroGiftLog),
            'bio'                => $this->contents,
        ];
    }
}

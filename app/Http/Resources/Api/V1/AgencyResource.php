<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isOwner = $this->app_owner_id == auth()->id();

        $owner = new \stdClass();
        if ($request->user () && ($isOwner)){
            // $owner = new \stdClass();
            $owner = new MiniUserResource($request->user ());
        }else{
            $owner = new MiniUserResource($this->owner);
        }
        return[
            'id' => $this->id ?? 0,
            'name' => $this->name ?? '',
            'notice' => $this->notice ?? '',
            'status' => $this->status ?? '',
            'phone' => $this->phone ?? '',
            'url' => $this->url ?? '',
            'img' => $this->img ?? '',
            'contents' => $this->contents ?? '',
            'payments' => $this->AgencypaymentGateways ?? [],
            'countries' => $this->countries ?? [],
            'owner' => $owner ?? null,

            $this->mergeWhen($isOwner, [
                'dollar' => $this->salary ?? 0,
                'coins' => $this->coins ?? 0,
                'salaryTransfer' => $this->transfer_salary ?? 0,
            ]),
        ]
            ;
    }
}

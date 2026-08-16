<?php

namespace Modules\SalaryTransaction\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FilterAgencyMangerResource extends JsonResource
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
        // if (($this->ownAgency?->Shipping_agency == 1) && ($this->ownAgency?->Host_agency == 1)) {
        //     $type = 'hosts and shipping';
        // } elseif (($this->ownAgency?->Shipping_agency == 0) && ($this->ownAgency?->Host_agency == 1)) {
        //     $type = 'hosts';
        // } elseif (($this->ownAgency?->Shipping_agency == 1) && ($this->ownAgency?->Host_agency == 0)) {
        //     $type = 'shipping';
        // }
        if ($this->ownAgency?->type == 1 ) {
            $type = 'hosts ';
         
        } elseif ($this->ownAgency?->type == 2) {
            $type = 'shipping';
        }
        return [
            'id' => $this->id,
            'agency_id' => (string)$this->agency_id ?? '0',
            'uuid' => $this->uuid,
            'name' => @$this->name,
            'image' => @$this->profile?->avatar,
            'total_member' => $this->ownAgency?->mempers->count(),
            'agency' => [
                'members' => AgencyMemberResource::collection($this->ownAgency?->mempers),
                'id' => $this->ownAgency?->id ?? 0,
                'name' => @$this->ownAgency?->name ?? '',
                'image' => @$this->ownAgency?->img ?? '',
                'agency_type' => $type,
            ],
        ];
    }
}

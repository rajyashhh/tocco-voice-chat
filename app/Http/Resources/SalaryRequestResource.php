<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        switch ($this->status) {
            case 0:
             $status = __('waiting');
              break;
            case 1:
                $status = __('accepting');
              break;
              case 2:
                $status = __('transferred');
              break;
              case 3:
                $status = __('completed');
              break;
              case 4:
                $status = $this->request_admin_status==1 ?__('rejectedAdmin'):__('rejected');
              break;
            }
    
        return [
            'id' => $this->id,
            'agency' => [
                'name'   => $this->agency->name ?? '',
                'image' => $this->agency->img ?? '',

            ],
            'owner' => [
                'name'   => $this->agencyOwner->name ?? '',
                'image'  => $this->agencyOwner?->profile?->avatar ?? '',

            ],
            'host' => [
                'name' => $this->host?->name ?? '',
                'image' => $this->host?->profile?->avatar ??'',
            ],
            'status' => $status,
            'payment_gateway' => [
                'title' => $this->payment_gateway->title ?? '',
                
            ],
            'country' => [
                'name' => $this->country?->name ?? '',
            ],
            'usd' => $this->usd,
            'coins' => $this->coins,
            'bill_image' => $this->bill_image ?? '',
           
        ];
    }
}

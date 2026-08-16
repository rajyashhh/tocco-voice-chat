<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class MyDataUtdResource extends JsonResource
{


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $achievement_images = [];
        if ($this->medals) {
            foreach ($this->medals as $medal) {
                if ($medal->achievementLevel) {
                    $data = [
                        'image' => @$medal->achievementLevel->valid_image,
                        'title' => @$medal->achievementLevel?->achievement?->name ?? '',
                        'created_at' => @$medal->created_at,
                    ];
                    $achievement_images[] = $data;
                }
            }
        }
        return [
            'id' => $this->id ?? 0,
            'name' => $this->name  ?? '',
            'uuid' => $this->uuid ?? 0,
            'image' => $this->profile->avatar ?? '',
            'bio' => @$this->bio ?: '',
            'coins' => $this->di ?? 0,
            'diamonds' => $this->exchange_diamonds ?? 0,
            'phone' => $this->phone ?? '',
            'email' => $this->email ?? '',
            'country' => new CountryResource(@$this->country),
            'level' => Common::level_center(@$this),
            'vip' => Common::ovip_center($this),
            'agency' => [
                'id' => $this->agency_id ?? 0,
                'image' => @$this?->agency?->img ?? '',
                'owner' => new OwnerAgencyResource(@$this?->agency?->owner)
            ],
            'achievement_images' => $achievement_images,
            'number_of_fans' => $this->followerss()->count(),
            'number_of_followings' => $this->following()->count(),
            'number_of_friends' => $this->friends()->count(),
            'profile_visitors' => $this->profileVisits()->count(),





        ];
    }
}

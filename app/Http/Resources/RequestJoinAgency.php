<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestJoinAgency extends JsonResource
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
                $status =  __('pending');
                break;
            case 1:
                $status =  __('accepted');
                break;
            case 2:
                $status = __('denied');
                break;
        }

        return [
            'id' => $this ->id,
            'user' => [
                'id' => $this->user->id ?? 0,
                'avatar' => $this->user->profile->avatar ?? '',
                'name' => $this->user->name ?? '',
                'nickname' => $this->user->nickname ?? '',
                'email' => $this->user->email ?? '',
                'di' => $this->user->di ?? 0,
            ],
            'agency' => [
                'id' => $this->agency->id ?? 0,
                'name' => $this->agency->name ?? '',
                'notice' => $this->agency->notice ?? '',
                'phone' => $this->agency->phone ?? '',
                'img' => $this->agency->img ?? '',
                'url' => $this->agency->url ?? '',
            ],
            'whatsapp' => $this->whatsapp ?? '',
            'status' => $status,
            'admin' => [
                'id' => $this->admin->id ?? 0,
                'avatar' => $this->admin->avatar ?? '',
                'username' => $this->admin->username ?? '',
            ],
        ];
    }
}

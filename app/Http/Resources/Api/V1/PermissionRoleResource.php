<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionRoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => __($this->name), // Translate permission name
            'pivot' => [
                'role_id' => $this->pivot->role_id,
                'permission_id' => $this->pivot->permission_id,
            ],
        ];
    }
}

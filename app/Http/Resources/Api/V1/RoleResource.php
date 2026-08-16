<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name === 'admin' ? __('admin.admin') : __($this->name), // Translate the role name
            'created_at' => @$this->created_at?->format('Y-m-d H:i:s'), // Format date if needed
            'updated_at' => @$this->updated_at?->format('Y-m-d H:i:s'), // Format date if needed
            'can_delete' => !in_array($this->slug, ['administrator', 'admin', 'developer', 'agency', 'charger']),
            'permissions' => PermissionRoleResource::collection($this->permissions->take(7)), // Use PermissionResource
        ];
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'admin_permissions';
    protected $guarded = [];

    public function permissionTypes()
    {
        return $this->hasMany(PermissionType::class, 'permission_id');
    }
}

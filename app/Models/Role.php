<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'admin_roles';

    protected $guarded = ['permissions'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'admin_role_permissions', 'role_id', 'permission_id');
    }
}

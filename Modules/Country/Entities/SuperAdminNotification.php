<?php

namespace Modules\Country\Entities;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuperAdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'model_id', 'model_type', 'title', 'message',
        'is_read', 'super_admin_id', 'read_at','data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopeUnreadNotifications($query)
    {
        return $query->whereNull('read_at');
    }
}

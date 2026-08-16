<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAdministrator extends Model
{
    use HasFactory;

    protected $table = 'room_administrators';

    protected $fillable = [
        'room_id',
        'user_id',
        'permissions',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        // NULL = all permissions (legacy admins keep full powers).
        'permissions' => 'array',
    ];

    /**
     * Get the room that owns the administrator
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the user who is the administrator
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who assigned this administrator
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

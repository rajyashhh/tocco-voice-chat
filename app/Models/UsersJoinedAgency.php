<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersJoinedAgency extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kickedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'kicked_by_admin');
    }

    public function kickedByApp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kicked_by_app');
    }
}

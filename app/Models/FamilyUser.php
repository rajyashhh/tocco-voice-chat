<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyUser extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'family_user';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

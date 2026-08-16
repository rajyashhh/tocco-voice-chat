<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEarnInvitation extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['id', 'parent_id', 'user_id', 'user_charge', 'parent_percentage',
                            'source_type',
                            'amount',
                            'charge_id',
                            'is_claimed',
                            'claimed_at',
                            'meta',
                        ];

    public function user()
    {
        return $this->belongsTo(User::class)->with('profile:id,user_id,avatar as image');
    }
}

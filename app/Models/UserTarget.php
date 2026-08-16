<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserTarget extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_target';

    protected $guarded = [];

    protected $casts = [
        'extras' => 'json',
    ];

    public function scopeOfAgency($q)
    {
        $user = Auth::user();
        if (Auth::user()->isRole('agency')) {
            $q->whereNotNull('agency_id')->where('agency_id', '=', @$user->agency_id);
        }
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

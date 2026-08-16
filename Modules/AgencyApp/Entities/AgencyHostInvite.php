<?php

namespace Modules\AgencyApp\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class AgencyHostInvite extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    public function userInvite()
    {
        return $this->belongsTo(User::class, 'user_invite_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }
}

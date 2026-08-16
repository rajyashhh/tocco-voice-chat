<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class ProfileVisitor extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'profile_visitors';

    protected $guarded = [];
}

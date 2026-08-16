<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserUnion extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_unions';
}

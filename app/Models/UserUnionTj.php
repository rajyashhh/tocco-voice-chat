<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserUnionTj extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_union_tj';
}

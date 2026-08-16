<?php

namespace Modules\CP\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserRelationAvilable extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];
}

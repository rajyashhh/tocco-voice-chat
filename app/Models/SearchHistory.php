<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'search_histories';
}

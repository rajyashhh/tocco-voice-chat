<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vip_prev extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'vip_prev';

    protected $fillable = [
        'o_vip_id',
        'o_vip_privilege_id',
    ];
}

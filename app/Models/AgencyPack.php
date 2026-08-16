<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyPack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['agency_id', 'sender_id', 'vip_user_id', 'dash_user_id', 'target_id', 'get_type',
        'type', 'num', 'expire', 'is_read', 'is_used', 'use_num', 'price', 'price_item', 'using', 'days',
    ];
}

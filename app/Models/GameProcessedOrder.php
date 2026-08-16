<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProcessedOrder extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = ['order_id', 'endpoint', 'created_at'];
}

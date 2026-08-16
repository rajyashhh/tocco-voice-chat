<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FairLuckWalletHistory extends Model
{
    public $timestamps = false; 

    protected $fillable = [
        'wallet_type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];
}

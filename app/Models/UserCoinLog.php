<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCoinLog extends Model
{
    use HasFactory;
    protected $table = 'user_coin_logs';

    protected $fillable = [
        'user_id',
        'type',
        'sub_type',
        'amount',
        'from_date',
        'to_date',
        'created_at',
        'updated_at',
        'previous_amount',
        'item_name',
        'amount_before',
        'helper_amount',
        'user_type',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

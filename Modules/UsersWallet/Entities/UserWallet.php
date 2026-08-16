<?php

namespace Modules\UsersWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{

    protected $table = 'users_wallets';
    protected $fillable = [
        'user_id',
        'balance',
        'cut_amount',
        'pending_amount',
        'target_id',
    ];

    public function logs()
    {
        return $this->hasMany(WalletLog::class);
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

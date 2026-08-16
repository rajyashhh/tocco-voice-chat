<?php

namespace Modules\Payment\Entities;

use App\Models\Coin;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCoinPayment extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'user_coin_payments';

    protected $guarded = [];

    protected $fillable = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function coin()
    {
        return $this->hasOne(Coin::class, 'id', 'coin_id');
    }

    protected static function newFactory()
    {
        //        return \Modules\Payment\Database\factories\UserCoinPaymentFactory::new();
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentWithdraw extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function payment_withdraw_type()
    {
        return $this->belongsTo(PaymentWithdrawType::class)->with('userWithdrawFields');
    }
}

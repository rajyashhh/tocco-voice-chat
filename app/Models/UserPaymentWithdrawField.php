<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentWithdrawField extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function payment_withdraw_field()
    {
        return $this->belongsTo(PaymentWithdrawField::class, 'payment_withdraw_field_id');
    }
}

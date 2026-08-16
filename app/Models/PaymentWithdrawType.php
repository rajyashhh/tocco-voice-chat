<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWithdrawType extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function withdrawFields()
    {
        return $this->hasMany(PaymentWithdrawField::class, 'payment_withdraw_type_id');
    }

    public function userWithdrawFields()
    {
        return $this->hasMany(UserPaymentWithdrawField::class, 'payment_withdraw_type_id');
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWithdrawField extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function withdrawType()
    {
        return $this->belongsTo(PaymentWithdrawType::class, 'payment_withdraw_type_id');
    }
}

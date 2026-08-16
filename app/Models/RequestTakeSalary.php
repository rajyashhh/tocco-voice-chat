<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestTakeSalary extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function paymentWithDraw()
    {
        return $this->belongsTo(PaymentWithdrawType::class, 'payment_withdraw_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php
namespace Modules\UsersWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class UserWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'payment_withdraw_type_id',
        'status',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function paymentWithdrawType()
    {
        return $this->belongsTo(\App\Models\PaymentWithdrawType::class, 'payment_withdraw_type_id');
    }
}

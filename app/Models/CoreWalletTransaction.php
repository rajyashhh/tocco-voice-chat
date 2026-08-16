<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreWalletTransaction extends Model
{

    protected $fillable = [
        'from_wallet',
        'to_wallet',
        'admin_id',
        'amount',
    ];

    public function admin()
    {
        return $this->belongsTo(AdminUser::class);
    }


}
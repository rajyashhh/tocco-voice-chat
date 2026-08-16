<?php
namespace Modules\UsersWallet\Entities;

use App\Models\Admin;
use App\Models\Target;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;


class WalletLog extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'operation',
        'type',
        'before_amount',
        'after_amount',
        'related_id',
        'operation_uuid',
    ];

    public function wallet()
    {
        return $this->belongsTo(UserWallet::class);
    }
        public function target()
    {
        return $this->belongsTo(Target::class, 'related_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'related_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

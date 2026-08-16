<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGameRanking extends Model
{
    protected $table = null;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'exp',
        'exp_diff',
        'exp_int',
        'remaining',
        'remaining_int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->with([
                'mangerType:id,name_ar,name_en,img',
                'UserVip:id,user_id,expire,level,is_used',
                'senderLevel:id,level,type,img',
                'receiverLevel:id,level,type,img',
                'country:id,name,iso,flag',
                'profile:user_id,avatar,birthday',
            ]);
    }
}
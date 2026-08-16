<?php

namespace Modules\SpecialId\Entities;

use App\Models\User;
use App\Models\Ware;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialHistory extends Model
{
    use HasFactory, TimestampsWithTimezone;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $table = 'special_id_histories';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ware()
    {
        return $this->belongsTo(Ware::class);
    }
}

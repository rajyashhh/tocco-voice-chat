<?php

namespace Modules\Reals\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Real extends Model
{
    use TimestampsWithTimezone;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY      = 'ready';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [];

    protected $guarded = [];

    public function scopeReady($query)
    {
        return $query->where('reals.status', self::STATUS_READY);
    }

    public function comments()
    {
        return $this->hasMany(RealUserComment::class, 'real_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(RealUserLike::class, 'real_id', 'id');
    }

    public function Views()
    {
        return $this->hasMany(RealUserView::class, 'real_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if ($model->description === null) {
                $model->description = '';
            }
        });
    }
}

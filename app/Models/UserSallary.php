<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\FixedTarget\Enums\TargetType;

class UserSallary extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_sallaries';

    protected $guarded = [];

    protected $casts = [
        'agency_sallary' => 'double',
        'sallary' => 'double',
        'extras' => 'json',
        'type' => TargetType::class,
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'user_agency_id');
    }

    public function target()
    {
        return $this->belongsTo(Target::class, 'target_id');
    }

    protected static function booted()
    {
        self::saved(function ($model) {
            if ($model->agency_id) {
                clearAgencyCache($model->agency_id);
            }
        });

        self::deleted(function ($model) {
            if ($model->agency_id) {
                clearAgencyCache($model->agency_id);
            }
        });
    }
}

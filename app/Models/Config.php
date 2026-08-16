<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if ($model->valueSelect) {
                unset($model->valueSelect);
            }
            if ($model->valueInteger) {
                unset($model->valueInteger);
            }
        });
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        // static::saving(function ($model) {
        //     // Transform the 'content' column to JSON format
        //     $model->content = json_encode($model->content);
        // });
    }
}

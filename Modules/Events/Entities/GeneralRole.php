<?php

namespace Modules\Events\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralRole extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    // Event URLs are stored relative (e.g. /weekly-star-view) so every
    // white-label client resolves them against its own APP_URL at read time.
    public function getUrlAttribute($value)
    {
        if ($value !== null && str_starts_with($value, '/')) {
            return url($value);
        }

        return $value;
    }
}

<?php

namespace Modules\TribeReward\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TribePeriod extends Model
{
    protected $fillable = ['start_date', 'end_date'];

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = date(
            'Y-m-d H:i:s',
            strtotime(convertArabicToEnglishNumbers($value))
        );
    }

    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = date(
            'Y-m-d H:i:s',
            strtotime(convertArabicToEnglishNumbers($value))
        );
    }

    public function tribeTops(): HasMany
    {
        return $this->hasMany(TribeTop::class);
    }
}

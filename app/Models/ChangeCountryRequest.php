<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeCountryRequest extends Model
{
    protected $fillable = ['country_id', 'user_id', 'status', 'old_country'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oldCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'old_country');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CharismaLevel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        $clearCache = fn () => Cache::forget('charisma_levels');

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}

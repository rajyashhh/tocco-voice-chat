<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class FamilyLevel extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'family_levels';

    protected $fillable = [
        'name',
        'img',
        'exp',
        'type',
        'members',
        'admins',
    ];

    protected static function booted(): void
    {
        // Clear cache when levels are created, updated, or deleted
        static::saved(fn() => \Cache::forget('family_levels_all'));
        static::deleted(fn() => \Cache::forget('family_levels_all'));
    }
}

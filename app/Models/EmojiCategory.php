<?php

namespace App\Models;

use App\Observers\EmojiCategoryObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmojiCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'title' => 'array',
    ];

    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];

    protected static function booted(): void
    {
        static::observe(EmojiCategoryObserver::class);

        static::saved(fn () => \Cache::forget('emoji_categories'));
        static::deleted(fn () => \Cache::forget('emoji_categories'));
    }
}

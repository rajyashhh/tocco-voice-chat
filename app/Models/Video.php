<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    protected $casts = [
        'shares_num' => 'integer',
        'comments_num' => 'integer',
        'likes_num' => 'integer',
        'views_num' => 'integer',
    ];

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

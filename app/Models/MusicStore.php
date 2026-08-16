<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicStore extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $table = 'music_store';
}

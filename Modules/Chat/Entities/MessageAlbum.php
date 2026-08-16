<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageAlbum extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('name', 'id', 'img');
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class);
    }
}

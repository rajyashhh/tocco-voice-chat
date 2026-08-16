<?php

namespace Modules\Chat\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReplay extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function from_message()
    {
        return $this->belongsTo(ChatMessage::class, 'from_message_id');
    }
}

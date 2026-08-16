<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTranslation extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'notification_translations';

    protected $fillable = ['notification_id', 'language', 'title', 'message'];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}

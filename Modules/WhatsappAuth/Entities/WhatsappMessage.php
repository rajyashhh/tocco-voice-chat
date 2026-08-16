<?php

namespace Modules\WhatsappAuth\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteAccount extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

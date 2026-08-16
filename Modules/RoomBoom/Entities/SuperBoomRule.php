<?php

namespace Modules\RoomBoom\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperBoomRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rules_ar',
        'rules_en',
        'content',
    ];
}

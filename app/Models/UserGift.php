<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGift extends Model
{
    use HasFactory;
    protected $table = 'user_gifts';
    protected $fillable = ['gift_id', 'user_id', 'quantity', 'expire'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetEdit extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'edited_by',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function target()
    {
        return $this->belongsTo(Target::class);
    }
}

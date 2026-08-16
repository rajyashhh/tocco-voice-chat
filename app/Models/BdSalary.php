<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdSalary extends Model
{
    use HasFactory;
    protected $fillable = [
        'bd_id',
        'salary',
        'cut_amount',
        'month',
        'year',
    ];

    public function bd()
    {
        return $this->belongsTo(Bd::class);
    }
}

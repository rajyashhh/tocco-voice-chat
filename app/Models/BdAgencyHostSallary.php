<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdAgencyHostSallary extends Model
{
    use HasFactory;

    protected $fillable = [
        'bd_id',
        'user_id',
        'agency_id',
        'amount',
        'salary',
        'month',
        'year',
        'bd_user_id',
        'created_at'
    ];

    public function bd()
    {
        return $this->belongsTo(Bd::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function userSallaries()
    {
        return $this->hasMany(UserSallary::class, 'user_agency_id', 'agency_id')
            ->whereColumn('month', 'bd_agency_host_sallaries.month')
            ->whereColumn('year', 'bd_agency_host_sallaries.year');
    }
}

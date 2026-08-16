<?php

namespace Modules\Country\Entities;

use App\Models\HomeCarousel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperadminBannerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'home_carousel_id', 'coins_deducted', 'status', 'notes','hours'
    ];

    public function superAdmin()
    {
        return $this->belongsTo(SuperAdmin::class, 'user_id', 'id');
    }

    public function homeCarousel()
    {
        return $this->belongsTo(HomeCarousel::class, 'home_carousel_id');
    }
}

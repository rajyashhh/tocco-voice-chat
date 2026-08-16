<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Http\Traits\AchievementGift;
use Modules\Moment\Entities\Moment;
use Modules\Vip\Entities\OVip;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gift extends Model
{
    use AchievementGift, TimestampsWithTimezone, HasFactory;


    public $sortable = [
        'order_column_name' => 'sort', // Set this to your column name
        'sort_when_creating' => true,
    ];
    // protected $fillable=['use_count'];
    protected $guarded = [];

    public function moments()
    {
        return $this->belongsToMany(Moment::class, 'moment_user_gifts')->withPivot('num', 'created_at', 'updated_at')->withTimestamps();
    }

    public function vip()
    {
        return $this->hasOne(OVip::class, 'id', 'vip_level');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_gifts')
            ->withPivot('quantity');
    }

    public function category()
    {
        return $this->belongsTo(GiftCategory::class, 'gift_category_id');
    }

    public function canPassToCp($cpEnableAllGifts)
    {
        if ($cpEnableAllGifts) {
            return true;
        }
        return $this->category && $this->category->type === 'cp';
    }

    public function scopeCpAllowed($query, $cpEnableAllGifts)
    {
        if ($cpEnableAllGifts) {
            return $query;
        }

        return $query->whereHas('category', function ($q) {
            $q->where('type', 'cp');
        });
    }
}

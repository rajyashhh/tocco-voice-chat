<?php

namespace Modules\Moment\Entities;

use App\Models\Gift;
use App\Models\MomentGallery;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Moment extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = ['user_id', 'description', 'img'];

    protected $table = 'moment';

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(MomentCommint::class, 'moment_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(MomentLikes::class, 'moment_id', 'id');
    }

    public function gifts()
    {
        return $this->belongsToMany(Gift::class, 'moment_user_gifts');
    }

    public function images()
    {
        return $this->hasMany(MomentGallery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'moment_user_gifts')->withPivot('num', 'created_at','updated_at');
    // }

    public function scopeLikeExists($query, $userId)
    {
        return $query->withExists(['likes' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }]);
    }


    public function scopeWithUser($query)
    {
        return $query->with(['user' => fn($q) => $q->select(['id', 'uuid','name', 'special_id', 'sender_level', 'received_level', 'charge_level', 'now_room_uid', 'type_user', 'manger_type_id', 'color_id', 'image_color_id', 'is_bd'])
            ->with([
                'packs' => fn($q) => $q->whereIn('type', [4, 25, 18])
                    ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
                    ->where('is_used', 1)
                    ->with(['ware:id,img1,img2,show_img,color,value']),
                'UserVip' => fn($q) => $q->with('OVip:id,img'),
                'receiverLevel:id,img,level',
                'senderLevel:id,img,level',
                'chargeLevel:id,img,level',
                'profile:id,user_id,avatar',
                'room:id,uid,room_pass',
                'shippingAgency:id,app_owner_id,name,img'
            ])]);
    }
}

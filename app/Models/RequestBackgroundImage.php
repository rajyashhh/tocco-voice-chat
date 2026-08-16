<?php

namespace App\Models;

use App\Helpers\Common;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class RequestBackgroundImage extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'request_background_images';

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $room = Room::where('uid', $model->owner_room_id)->first();
            if ($model->status === 1) {
                if (! empty($room)) {
                    $data = [
                        'messageContent' => [
                            'message' => 'changeBackground',
                            'imgbackground' => $model->img ?: '',
                            'roomIntro' => $room?->room_intro ?: '',
                            'roomImg' => $room?->room_cover ?: '',
                            'room_type' => @$room?->myType->name ?: '',
                            'room_name' => @$room?->room_name ?: '',
                        ],
                    ];
                    $json = json_encode($data);
                    $res = Common::sendToStream('SendCustomCommand', $room?->id, $model->owner_room_id, $json);
                }
            }


            $model->created_by = \Auth::id();
            $model->created_by_type = get_class(\Auth::user());
            $model->room_id = $room->id;

            if ($model->created_by_type === User::class){

            }


        });


    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_room_id');
    }

    public function creator()
    {
        return $this->morphTo(null, 'created_by_type', 'created_by');
    }
}

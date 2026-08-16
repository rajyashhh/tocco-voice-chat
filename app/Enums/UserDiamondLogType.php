<?php

namespace App\Enums;

enum UserDiamondLogType: string
{

    
    case GIFT_ROOM_AUDIO = 'gift_room_audio';
    case GIFT_ROOM_LIVE = 'gift_room_live';
    case EXCHANGE = 'exchanges_diamonds';
    case MOMENT = 'moment';


    public function meta(): array
    {
        return match ($this) {

            self::EXCHANGE => [
                'sub_type' => 'users',
                'item_name' => 'exchanges_diamonds',

            ],


            self::GIFT_ROOM_AUDIO => [
                'sub_type' => 'gift_logs',
                'item_name' => 'gift',

            ],

            self::GIFT_ROOM_LIVE => [
                'sub_type' => 'gift_logs',
                'item_name' => 'gift',

            ],

            self::MOMENT => [
                'sub_type' => 'moment',
                'item_name' => 'moment',
            ],
        };
    }
}

<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;


/**
 * @method static checkUserIsAdminOrOwner(string $room_admin, int $owner_id)
 * @method static gameWhoWin($answer_player_two, $answer_player_two1, $record_game)
 */
class RoomHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'RoomHelper';
    }


}

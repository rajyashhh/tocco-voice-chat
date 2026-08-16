<?php

namespace App\Services;

use App\Helpers\Common;
use App\Models\User;
use App\Models\UserTarget;
use App\Models\UserGameChallange;
use App\Models\Room;
class GameServices
{
    public function send_reuest_paly($user ,$request){
        $player_two=User::find($request->player_two_id);
        if ($user->di < $request->coins) {return Common::apiResponse (0,'you dont have this number of coins',null,422); }
        if ($player_two->di < $request->coins) {return Common::apiResponse (0,'player two dont have this number of coins',null,422); }
        $room=Room::where("uid",$request->owner_id)->first();
        $data=new UserGameChallange();
        $data->room_id=$room->id??0;
        $data->game_id=$request->game_id;
        $data->player_one_id=$user->id;
        $data->player_two_id=$request->player_two_id;
        $data->coins=$request->coins;
        $data->save();
        return $data;
    }

    
}

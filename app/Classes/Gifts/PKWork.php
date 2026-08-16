<?php

namespace App\Classes\Gifts;

use App\Helpers\Common;
use App\Interfaces\RoomJobInterface;
use App\Models\Pk;
use App\Models\Room;
use Carbon\Carbon;

class PKWork implements RoomJobInterface
{

    public function work($roomJob) : array
    {
        $room = Room::query()->find($roomJob->room_id);
        if(!$room) throw \Exception('Room not found');
        $userIds = unserialize($roomJob->data, ['allowed_classes' => false]);
        $earnedCoinsPerUser = $roomJob->coins;
        $lastPk =
            Pk::query()->where('room_id', $room->id)->where('status', 1)->whereDate('end_at', "<=", now())->orderByDesc('id')->first();
        $data = (new SendGiftService())->updatePkScoresAndSendToStreamJob2($lastPk,$userIds, $earnedCoinsPerUser, $room);
        return ['room_id'=> $room->id, ...$data];
    }

    public function sendToStream($data, int $roomId,int $user_id):  string
    {
        $ms = [
            'messageContent' => [
                "message" => "updatePk",
                "PkTime" => Carbon ::parse ( $data['end_at']  ) -> diffInMinutes ( now () ),
                "scoreTeam1" => $data['t1_score'],
                "scoreTeam2" => $data['t2_score'],
                "percentagepk_team1" => (string)$data['t1_per'] ,
                "percentagepk_team2" => (string)$data['t2_per']
            ]
        ];
        $json = json_encode ($ms);
        return $json;
    }

    public function getVariables($data) : array
    {
        return [$data, $data['room_id'], 2013];
    }

    public function prepareDataToStream($data) : array
    {
        $grouped = [];
        foreach ($data['pk'] as $pk) {
            if (!isset($grouped[$pk['room_id']])) {
                $grouped[$pk['room_id']] = [
                    'room_id' => $pk['room_id'],
                    't1_score' => 0.0,
                    't2_score' => 0.0,
                    't1_per' => 0,
                    't2_per' => 0,
                ];
            }

            $grouped[$pk['room_id']]['end_at'] = $pk['end_at'] ?? null;
            $grouped[$pk['room_id']]['t1_score'] += $pk['t1_score'];
            $grouped[$pk['room_id']]['t2_score'] += $pk['t2_score'];
            $grouped[$pk['room_id']]['t1_per'] = $this->per_1($grouped[$pk['room_id']]['t1_score'],$grouped[$pk['room_id']]['t2_score']);
            $grouped[$pk['room_id']]['t2_per'] = $this->per_2($grouped[$pk['room_id']]['t1_score'],$grouped[$pk['room_id']]['t2_score']);
        }
        $finalResult = array_values($grouped);
        return $finalResult;
    }

    public function per_2($t1_score,$t2_score) :float
    {
        if (($t1_score + $t2_score) > 0){
            $res = $t2_score/($t1_score + $t2_score);
        }else{
            $res = 0.5;
        }
        return number_format ($res,2);
    }

    public function per_1($t1_score,$t2_score) :float
    {
        if (($t1_score + $t2_score) > 0){
            $res = $t1_score/($t1_score + $t2_score);
        }else{
            $res = 0.5;
        }
        return number_format ($res,2);
    }
}

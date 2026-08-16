<?php

namespace App\Observers;

use App\Models\Room;
use Illuminate\Support\Facades\Log;

class RoomObserver
{
    public function creating(Room $room)
    {
        if ($room->type == 'audio') {
            $room->mode = 3;
        }
        $room->muted_users = '';
    }

    public function updating(Room $room)
    {
        if (!$room->enableSaving) return;

        if ($room->type  == 'audio') {

            $this->changeMode($room);
        }
        $this->resetRoomSession($room);
    }

    public function saving(Room $room)
    {
        if (!$room->enableSaving) return;

        if ($room->type  == 'audio') {
            $this->changeMode($room);
        }
        //        $this->resetRoomSession ($room);
        //        $v = $room->room_visitor;
        //        $av = explode (',',$v);
    }
    public function changeMode(Room &$room)
    {
        if ($room->isDirty('mode')) {
            \App\Models\RoomMicrophone::where('room_id', $room->id)->delete();
            
            $mics = explode(',', $room->all_microphone);
            $count = count($mics);


            if ($room->mode == '0') {
                if ($count <= 10) {
                    $m = array_merge($mics, array_fill(0, 10 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 10);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '1') { //16 seats
                if ($count <= 17) {
                    $m = array_merge($mics, array_fill(0, 17 - $count, '0'));
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '2') { //12 seats
                if ($count <= 13) {
                    $m = array_merge($mics, array_fill(0, 13 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 15);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '3') { //9 seats
                if ($count <= 10) {
                    $m = array_merge($mics, array_fill(0, 10 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 10);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '4') { //4 seats
                if ($count <= 3) {
                    $m = array_merge($mics, array_fill(0, 4 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 4);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '5') { //3 seats
                if ($count <= 10) {
                    $m = array_merge($mics, array_fill(0, 10 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 10);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '6') { //2 seats
                if ($count <= 3) {
                    $m = array_merge($mics, array_fill(0, 3 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 3);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '7') { //22 seats
                if ($count <= 23) {
                    $m = array_merge($mics, array_fill(0, 23 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 23);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '8') { //8 seats
                if ($count <= 9) {
                    $m = array_merge($mics, array_fill(0, 9 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 10);
                    $room->microphone = implode(',', $m);
                }
            } elseif ($room->mode == '9') { //8 seats
                if ($count <= 9) {
                    $m = array_merge($mics, array_fill(0, 9 - $count, '0'));
                    $room->microphone = implode(',', $m);
                } else {
                    $m = array_slice($mics, 0, 9);
                    $room->microphone = implode(',', $m);
                }
            } else { // 8 seats
                if ($count > 10) {
                    $m = array_slice($mics, 0, 10);
                    $room->microphone = implode(',', $m);
                }
            }
        }
    }

    public function resetRoomSession($room)
    {
        $owner_in = $room->is_afk;

        // Check if room has no visitors using relation
        $room->loadMissing('roomVisitors');
        if ($room->roomVisitors->isEmpty() && $owner_in != 1) {
            $room->room_speak = null;
        }
    }
}

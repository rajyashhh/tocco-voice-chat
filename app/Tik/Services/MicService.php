<?php

namespace App\Tik\Services;

use App\Facades\RoomHelper;
use App\Helpers\Common;
use App\Models\Cp;
use App\Models\GiftLog;
use App\Tik\Repositories\LiveTimeRepository;
use App\Tik\Repositories\PkRepository;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\TimeLogRepository;
use App\Tik\Repositories\UserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\CP\Entities\CpRoomHistory;
use Modules\CP\Enums\CpStatus;

class MicService
{
    public function __construct(

        private readonly RoomRepository $roomRepository,
        private readonly LiveTimeRepository $liveTimeRepository,
        private readonly UserRepository $userRepository,
        private readonly PkRepository $pkRepository,
        private readonly TimeLogRepository $timeLogRepository,

    ) {}


    public function createLiveTime($userId, $seconde)
    {
        $hours = $seconde / 3600;
        $intValue = (int)$hours;
        $minutes = round(($hours - $intValue) * 60);
        $totalTime = sprintf('%02d:%02d', $intValue, $minutes);
        $hoursFormat          = number_format($hours, 5, '.', '');
        $data = [
            'start_time' => Carbon::now()->subSecond($seconde)->timestamp,
            'end_time'   => Carbon::now()->timestamp,
            'hours'    => $hoursFormat,
            'uid'     => $userId,
        ];

        $this->liveTimeRepository->create($data);

        $user_hours =  $this->liveTimeRepository->totalHoursUser($userId);

        $hours = (int)$user_hours;

        return [$hours, $totalTime];
    }

    public function upMic($data)
    {
        $user = $this->userRepository->findById($data['user_id']);

        if (!$user) throw new Exception(__('api_responses.this_user_not_found'));
        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);

        if (!$room)  throw new Exception(__('room does not exist'));
        $data['owner_id'] = $room->uid;
        //
        $position = $data['position']; // mic index
        $mic_arr = explode(',', $room->microphone);
        $main_mic = explode(',', $room->main_microphone);
        $base_mic = explode(',', $room->microphone_only_users);

        if (!isset($mic_arr[$position])) {
            throw new Exception(__('This seat is out of the designated range'));
        } elseif ($position == 0 && $room->uid != \Auth::id()) {
            throw new Exception(__('This seat is for owner'));
        }

        $current = $mic_arr[$position] ?? '0';
        $old_status = '0';
        $old_user = '0';

        if (str_contains($current, '#')) {
            [$old_user, $old_status] = explode('#', $current);
        } elseif (is_numeric($current) && (int)$current > 0) {
            $old_user = $current;
            $old_status = '0'; // Assume occupied but no explicit status
        } else {
            $old_user = '0';
            $old_status = $current;
        }

        if ($main_mic[$position] == '-1' && !RoomHelper::checkUserIsAdminOrOwner($room->room_admin ?? '', $data['owner_id'])) {
            throw new Exception(__('This microphone is closed and cannot be accessed'));
        }


        if (in_array($user->id, $mic_arr)) {
            CpRoomHistory::where("user_one_id", $user->id)
                ->orWhere("user_two_id", $user->id)->delete();

            $key = array_search($user->id, $mic_arr);
            $old = $main_mic[$key] ?? '0';
            $base_mic[$key] = $old;
        }

        $base_mic[$position] = $user->id . '#' . $old_status;

        $mic = implode(',', $base_mic);

        $this->updateMicAndPK($room, $mic);
        //Remove mic sequence
        Common::delMicHand($user->id);

        $t = $this->liveTimeRepository->getActiveByUserId($user->id);
        if (!$t) {

            $data = [
                'uid' => $user->id,
                'start_time' => time()
            ];
            $this->liveTimeRepository->create($data);
        }
        $this->handleCpLovely($user, $room, $position);

        return [$user, $room];
    }

    public function upMic2($data)
    {
        $user = $this->userRepository->findById($data['user_id']);

        if (!$user) throw new Exception(__('api_responses.this_user_not_found'));
        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);

        if (!$room)  throw new Exception(__('room does not exist'));
        $data['owner_id'] = $room->uid;

        $position = (int) $data['position'];

        $modeMaxSeats = [
            '0' => 8, '1' => 15, '2' => 11, '3' => 8, '4' => 3,
            '5' => 7, '6' => 1, '7' => 21, '8' => 7, '9' => 7,
        ];
        $maxPositions = $modeMaxSeats[$room->mode] ?? 8;
        if ($position < 0 || $position > $maxPositions) {
            throw new Exception(__('api_responses.position_error'));
        }
        if ($position == 1 && $room->mode == 7) {
            $this->checkTopUserMood($user->id, $room->id);
        }
        $micSeat = $room->microphones()->where('position', $position)->first();

        if (!$micSeat) {
            $micSeat = $room->microphones()->create([
                'position' => $position,
                'status'   => 1,
                'user_id'  => null,
            ]);
        }

        $old_user   = $micSeat?->user_id ?? 0;
        $old_status = $micSeat?->status ?? '0';

        if ($micSeat->status == -1 && !RoomHelper::checkUserIsAdminOrOwner($room->room_admin ?? '', $room->uid)) {
            throw new Exception(__('This microphone is closed and cannot be accessed'));
        }

        $existingMic = $room->microphones()->where('user_id', $user->id)->first();
        if ($existingMic) {

            if ($existingMic->position !== $position) {

                CpRoomHistory::where("user_one_id", $user->id)
                    ->orWhere("user_two_id", $user->id)
                    ->delete();

                $existingMic->delete();
            } else {
            }
        }


        $micSeat->update([
            'user_id' => $user->id,
            'status'  => $old_status,
        ]);

        $micString = $room->microphones()
            ->orderBy('position')
            ->get()
            ->map(function ($mic) {
                $userId = $mic->user_id ?? 0;
                $status = $mic->status ?? 0;

                if ($userId > 0) {
                    return "{$userId}#{$status}";
                } else {
                    return (string)$status;
                }
            })
            ->implode(',');

        $this->updatePK($room, $micString);
        //Remove mic sequence
        Common::delMicHand($user->id);

        $t = $this->liveTimeRepository->getActiveByUserId($user->id);
        if (!$t) {
            $data = [
                'uid' => $user->id,
                'start_time' => time()
            ];
            $this->liveTimeRepository->create($data);
        }
        $this->handleCpLovely($user, $room, $position);

        return [$user, $room];
    }

    protected function checkTopUserMood($userId, $roomId)
    {
        $timezone = Common::timeZone();

        $topSenderId = GiftLog::where('room_id', $roomId)
            ->whereBetween('created_at', [
                Carbon::now($timezone)->startOfDay(),
                Carbon::now($timezone)->endOfDay()
            ])
            ->selectRaw("SUM(giftPrice) as total, sender_id")
            ->groupBy('sender_id')
            ->orderByDesc('total')
            ->value('sender_id');

        if ($topSenderId != $userId) {
            throw new Exception(__('Send more gifts to become the top sender and set mic'));
        }

        return true;
    }

    public function handleCpLovely($user, $room, $position)
    {


        $existingCps = Cp::where(function ($query) use ($user) {
            $query->where("user_one_id", $user->id)
                ->orWhere("user_two_id", $user->id);
        })->whereIn("status", [
            CpStatus::ACTIVE,
            CpStatus::RESTORED
        ])->get();

        if ($existingCps->isEmpty()) {
            return false;
        }

        if ($room->mode == 0 && $position == 0) {
            return true;
        }


        $userSeats = $this->getUserNearby($position, $room->mode);



        //        $micSeats = array_map(function ($v) {
        //            return is_numeric($v) ? (int)$v : null;
        //        }, explode(',', $room->microphone));

        $micSeats = $room->microphones()
            ->orderBy('position')
            ->pluck('user_id', 'position')
            ->toArray();

        foreach ($userSeats as $neighborPosition) {

            $userOtherId = $micSeats[$neighborPosition] ?? null;



            $existingCp = $this->checkExistingCpLovly($user->id, $userOtherId);
            if ($existingCp) {

                $this->handleCpRoomHistory($user, $room, $position, $neighborPosition, $userOtherId);
                $this->sendCpLovelyMessage($room, $user);

                return true;
            }
        }

        $this->sendCpLovelyMessage($room, $user);

        return true;
    }

    public function getUserNearby($index, $mode)
    {
        // $neighbors = [];

        // $rowSize = match ($mode) {
        //     1 => 16,
        //     2 => 12,
        //     default => 9,
        // };

        // $rowStart = intdiv($index - 1, $rowSize) * $rowSize + 1;
        // $rowEnd = $rowStart + $rowSize - 1;
        // if ($rowSize <= 1) {
        //     return [];
        // }


        // if ($index - 1 >= $rowStart) {
        //     $neighbors[] = $index - 1;
        // }
        // if ($index + 1 <= $rowEnd) {
        //     $neighbors[] = $index + 1;
        // }

        // return $neighbors;

        $rowSize = 4;

        // $rowStart = intdiv($index, $rowSize) * $rowSize;
        // $rowEnd = $rowStart + $rowSize - 1;

        // $neighbors = [];

        // for ($i = $rowStart; $i <= $rowEnd; $i++) {
        //     if ($i !== $index) {
        //         $neighbors[] = $i;
        //     }
        // }
        $rowStart = intdiv($index - 1, $rowSize) * $rowSize + 1;
        $rowEnd = $rowStart + $rowSize - 1;
        if ($rowSize <= 1) {
            return [];
        }


        if ($index - 1 >= $rowStart) {
            $neighbors[] = $index - 1;
        }
        if ($index + 1 <= $rowEnd) {
            $neighbors[] = $index + 1;
        }


        return $neighbors;
    }



    public function sendCpLovelyMessage($room, $user)
    {
        $cpRoomHistories = CpRoomHistory::where("room_id", $room->id)->get(['index1', 'index2']);
        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        $json = $this->cpMapJson($indices);

        Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
    }
    public function cpMapJson($indices): string|false
    {
        $ms = [
            'messageContent' => [
                "message" => "cpLovelyZego",
                "data" => $indices,
            ]
        ];
        $json = json_encode($ms);
        return $json;
    }
    public function handleCpRoomHistory($user, $room, $index1, $index2, $userOtherId)
    {
        $cpRoomHistory = CpRoomHistory::where("user_one_id", $user->id)
            ->where("user_two_id", $userOtherId)
            ->orWhere(function ($query) use ($user, $userOtherId) {
                $query->where("user_two_id", $user->id)
                    ->where("user_one_id", $userOtherId);
            })
            ->where('room_id', $room->id)
            ->first();

        if ($cpRoomHistory) {
            $cpRoomHistory->index1 = $index1;
            $cpRoomHistory->index2 = $index2;
            $cpRoomHistory->save();
        } else {
            CpRoomHistory::create([
                'room_id' => $room->id,
                'user_one_id' => $user->id,
                'user_two_id' => $userOtherId,
                'index1' => $index1,
                'index2' => $index2,
            ]);
        }
    }
    public function checkExistingCpLovly($userId, $otherUserId)
    {
        return Cp::where(function ($query) use ($userId, $otherUserId) {
            $query->where("user_one_id", $userId)
                ->where("user_two_id", $otherUserId)
                ->orWhere(function ($query) use ($userId, $otherUserId) {
                    $query->where("user_two_id", $userId)
                        ->where("user_one_id", $otherUserId);
                });
        })->relation()
            /// TODO convert these status to enum
            ->whereIn("status", [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            // ->where("cp_relation_id",5)
            ->first();
    }

    public function goMic($data)
    {
        $user = $this->userRepository->findById($data->user_id);

        if (!$user) throw new Exception(__('api_responses.this_user_not_found'));

        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);
        if (!$room) throw new Exception(__('api_responses.room_not_found'));

        $this->goMicrophoneHand($user, $room);
        return $room;
    }

    public function goMic2($data)
    {
        $user = $this->userRepository->findById($data->user_id);

        if (!$user) throw new Exception(__('api_responses.this_user_not_found'));

        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);
        if (!$room) throw new Exception(__('api_responses.room_not_found'));

        $this->goMicrophoneHand2($user, $room);
        return $room;
    }

    //Down the wheat - execute the operation
    // public  function goMicrophoneHand($user, $room)
    // {
    //     $microphone = $room->microphone;
    //     $mainMicrophone = $room->main_microphone;

    //     $baseMic = $room->getOriginal('microphone');
    //     $microphone = explode(',', $microphone);
    //     $mainMicrophone = explode(',', $mainMicrophone);
    //     $baseMic = explode(',', $baseMic);
    //     if (!$microphone || !in_array($user->id, $microphone)) {
    //         return 0;
    //     }
    //     $position = 0;
    //     for ($i = 0; $i < count($microphone); $i++) {
    //         if ($microphone[$i] == $user->id) {
    //             $position = $i;
    //             break;
    //         }
    //     }
    //     if ($microphone[$position] > 0) {
    //         $baseMic[$position] = $mainMicrophone[$position];
    //     }

    //     $microphone = implode(',', $baseMic);
    //     $this->updateMicAndPK($room, $microphone);
    //     //clear timer
    //     $this->timeLogRepository->deleteAth($room->uid, $user->id);

    //     $this->handleLeaveCp($user, $room);

    //     return true;
    // }

    public function goMicrophoneHand($user, $room)
    {

        $microphone = explode(',', $room->microphone);
        $mainMicrophone = explode(',', $room->main_microphone);
        $original = explode(',', $room->getOriginal('microphone'));
        if (!$microphone || !in_array($user->id, $microphone)) {
            return 0;
        }

        $position = array_search($user->id, $microphone);
        if ($position === false) return 0;

        // Remove user
        $microphone[$position] = "0";

        $final = [];

        for ($i = 0; $i < count($microphone); $i++) {
            $mic = $microphone[$i] ?? '0';
            $main = $mainMicrophone[$i] ?? '0';

            if ($mic != '0' && $main != '0' && $mic != $main) {
                $final[] = $mic . '#' . $main;
            } elseif ($mic != '0') {
                $final[] = $mic;
            } elseif ($main != '0') {
                $final[] = $main;
            } else {
                $final[] = '0';
            }
        }

        // Save to DB
        $result = implode(',', $final);
        $this->updateMicAndPK($room, $result);

        // Clear mic timer and leave CP
        $this->timeLogRepository->deleteAth($room->uid, $user->id);
        $this->handleLeaveCp($user, $room);

        return true;
    }

    public function goMicrophoneHand2($user, $room)
    {
        //        $microphone = explode(',', $room->microphone);
        //        $mainMicrophone = explode(',', $room->main_microphone);
        //        $original = explode(',', $room->getOriginal('microphone'));
        //        if (!$microphone || !in_array($user->id, $microphone)) {
        //            return 0;
        //        }

        $micSeat = $room->microphones()
            ->where('user_id', $user->id)
            ->first();

        if (!$micSeat) {
            return 0;
        }

        //        $position = array_search($user->id, $microphone);
        //        if ($position === false) return 0;

        $micSeat->delete();
        //        $micSeat->update([
        //            'user_id' => null,
        //            'status'  => 0,
        //        ]);

        // Remove user
        //        $microphone[$position] = "0";
        //
        //        $final = [];
        //
        //        for ($i = 0; $i < count($microphone); $i++) {
        //            $mic = $microphone[$i] ?? '0';
        //            $main = $mainMicrophone[$i] ?? '0';
        //
        //            if ($mic != '0' && $main != '0' && $mic != $main) {
        //                $final[] = $mic . '#' . $main;
        //            } elseif ($mic != '0') {
        //                $final[] = $mic;
        //            } elseif ($main != '0') {
        //                $final[] = $main;
        //            } else {
        //                $final[] = '0';
        //            }
        //        }

        $micString = $room->microphones()
            ->orderBy('position')
            ->get()
            ->map(function ($m) {
                $userId = $m->user_id ?? 0;
                $status = $m->status ?? 0;
                return "{$userId}#{$status}";
            })
            ->implode(',');

        // Save to DB
        //        $result = implode(',', $final);
        //        $this->updateMicAndPK($room, $result);

        $this->updatePK($room, $micString);

        // Clear mic timer and leave CP
        $this->timeLogRepository->deleteAth($room->uid, $user->id);
        $this->handleLeaveCp($user, $room);

        return true;
    }

    public function handleLeaveCp($user, $room)
    {
        $userId = $user->id;
        $this->removeUserCpInRoom($userId);
        return $this->sendCpLovelyMessage($room, $user);
    }
    public function removeUserCpInRoom(mixed $userId): void
    {
        CpRoomHistory::where("user_one_id", $userId)
            ->orWhere("user_two_id", $userId)->delete();
    }
    public function mic($data, $type)
    {
        $user = request()->user();
        $position = $data['position'];
        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);
        if (!$room) throw new Exception(__('room fot found'));
        if ($user->id != $room->uid && !in_array($user->id, $room->admins ?? [])) {
            throw new Exception(__('you do not have permission'));
        }
        $data['owner_id'] = $room->uid;

        if ($room['mode'] == 0) {
            if ($position < 0 || $position > 9) throw new Exception(__('api_responses.position_error'));
        } else {
            if ($position < 0 || $position > 17) throw new Exception(__('api_responses.position_error'));
        }
        $admins = $room->room_admin;
        $admins = explode(',', $admins);

        if ($data->user()->id != $data['owner_id'] && !in_array($data->user()->id, $admins)) {
            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'), null, 408);
        }


        // $microphone = $room->getOriginal('microphone');
        $microphone = $room->all_microphone;
        //        logger('microphone:', [$microphone]);

        $microphone = $this->micType($type, $microphone, $position);
        //        logger(' end microphone:', [$microphone]);

        $this->updateMic($room, $microphone);
        return $room;
    }

    public function mic2($data, $type)
    {
        $user = request()->user();
        $position = $data['position'];
        $roomId = $data->room_id;
        $room = $roomId
            ? $this->roomRepository->findById($roomId)
            : $this->roomRepository->findRoomUserEnableAudio($data['owner_id']);
        if (!$room) throw new Exception(__('room fot found'));
        //        if ($user->id != $room->uid && !in_array($user->id, $room->admins ?? [])) {
        //            throw new Exception(__('you do not have permission'));
        //        }
        //        $data['owner_id'] = $room->uid;
        //
        //        if ($room['mode'] == 0) {
        //            if ($position < 0 || $position > 9) throw new Exception(__('api_responses.position_error'));
        //        } else {
        //            if ($position < 0 || $position > 17) throw new Exception(__('api_responses.position_error'));
        //        }
        //        $admins = $room->room_admin;
        //        $admins = explode(',', $admins);
        //
        //        if ($data->user()->id != $data['owner_id'] && !in_array($data->user()->id, $admins)) {
        //            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'), null, 408);
        //        }

        $admins = $room->room_admin ? explode(',', $room->room_admin) : [];

        if ($user->id != $room->uid && !in_array($user->id, $admins)) {
            throw new Exception(__('you do not have permission'));
        }

        $data['owner_id'] = $room->uid;

        // position validation based on room mode
        $modeMaxSeats = [
            '0' => 8, '1' => 15, '2' => 11, '3' => 8, '4' => 3,
            '5' => 7, '6' => 1, '7' => 21, '8' => 7, '9' => 7,
        ];
        $maxPositions = $modeMaxSeats[$room->mode] ?? 8;
        if ($position < 0 || $position > $maxPositions) {
            throw new Exception(__('api_responses.position_error'));
        }

        // $microphone = $room->getOriginal('microphone');
        //        $microphone = $room->all_microphone;
        //        logger('microphone:', [$microphone]);

        //        $microphone = $this->micType($type, $microphone, $position);
        //        logger(' end microphone:', [$microphone]);

        $micSeat = $room->microphones()->where('position', $position)->first();

        if (!$micSeat) {
            $micSeat = $room->microphones()->create([
                'position' => $position,
                'user_id'  => null,
                'status'   => 0,
            ]);
        }

        $this->micType2($type, $micSeat);

        return $room;
    }

    public function micType2(string $type, $micSeat)
    {
        $userId = $micSeat->user_id ?? 0;
        $status = $micSeat->status ?? 0;

        if ($type === 'mute') {
            $status = -2;
        } elseif ($type === 'unmute' || $type === 'open') {
            $status = 0;
        } elseif ($type === 'shut') {
            $status = -1;
        }

        $micSeat->update([
            'status' => $status,
        ]);

        return $micSeat;
    }

    public function kickMicrophone($data)
    {
        $room = $this->roomRepository->findRoom($data['room_id']);

        if (! $room) {
            return Common::apiResponse(0, __('api_responses.room_not_found'), null, 408);
        }

        $admins = $room->room_admin;
        $admins = explode(',', $admins);

        if ($data->user()->id != $room->uid && !in_array($data->user()->id, $admins)) {
            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'), null, 408);
        }

        return Common::apiResponse(true, __('success'), []);
    }


    public function micType(string $type, $microphone, $position)
    {

        $microphone = explode(',', $microphone);
        $current = $microphone[$position] ?? '0';
        //        logger(' explode microphone:', [$microphone]);

        $user = '0';
        $status = '0';

        if (str_contains($current, '#')) {
            [$user, $status] = explode('#', $current);
        } elseif (is_numeric($current) && (int)$current > 0) {
            $user = $current;
            $status = '-1';
        } else {
            $user = '0';
            $status = $current;
        }

        if ($type === 'mute') {
            $status = '-2';
        } elseif ($type === 'unmute' || $type === 'open') {
            $status = '0';
        } elseif ($type === 'shut') {
            $status = '-1';
        }

        if ($user !== '0') {
            $microphone[$position] = $user . '#' . $status;
        } else {
            $microphone[$position] = $status;
        }
        //        logger('implode microphone:', [implode(',', $microphone)]);

        return implode(',', $microphone);
        // $microphone = explode(',', $microphone);
        // if ($type == 'mute') {
        //     if (@$microphone[$position] != -1) {
        //         $microphone[$position] = -2;
        //     }
        // } elseif ($type == 'unmute' || $type == 'open') {
        //     if (@$microphone[$position]) {
        //         $microphone[$position] = 0;
        //     }
        // } elseif ($type == 'shut') {
        //     if (@$microphone[$position] == false) {
        //         $microphone[$position] = -1;
        //     }
        // }
        // return $microphone = implode(',', $microphone);
    }



    public function updateMicAndPK($room, $mic)
    {
        $this->updateMic($room, $mic);
        $pk = $this->pkRepository->getPk($room->id);
        if ($pk) {
            $pk->mics = $room->microphone;
            $pk->save();
        }
    }

    public function updatePK($room, $mic)
    {
        $pk = $this->pkRepository->getPk($room->id);
        if ($pk) {
            $pk->mics = $mic;
            $pk->save();
        }
    }

    public function updateMic($room, $mic)
    {
        $this->roomRepository->updateMicRoom($room, $mic);
    }
}

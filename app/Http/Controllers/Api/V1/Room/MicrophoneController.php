<?php

namespace App\Http\Controllers\Api\V1\Room;

use Exception;
use App\Models\Pk;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Helpers\Common;
use App\Models\LiveTime;
use Carbon\CarbonInterval;
use App\Facades\RoomHelper;
use App\Models\EnteredRoom;
use Illuminate\Http\Request;
use App\Facades\UserHandling;
use App\Tik\Services\MicService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CP\Http\Services\CpServices;
use Illuminate\Support\Facades\Validator;

class MicrophoneController extends Controller
{

    protected $microphoneService;

    public function __construct(MicService $microphoneService)
    {
        $this->microphoneService = $microphoneService;
    }




    // on the mic

    public function upMicrophone(Request $request)
    {
        $data = $request;
        $user_id = $request->user_id;
        if ((!$data['owner_id'] && !$request->room_id) || !$user_id) return Common::apiResponse(0, __('api_responses.Missing_data'), null, 422);
        try {
            [$user, $room] = $this->microphoneService->upMic($data);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        // (new CpServices())->sendStreamMap($user, $room);
        return Common::apiResponse(1, __('api_responses.Success_on_the_mic'));
    }
    //leave mic

    public function upMicrophone2(Request $request)
    {
        $data = $request;
        $user_id = $request->user_id;
        if ((!$data['owner_id'] && !$request->room_id) || !$user_id) return Common::apiResponse(0, __('api_responses.Missing_data'), null, 422);
        try {
            [$user, $room] = $this->microphoneService->upMic2($data);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        // (new CpServices())->sendStreamMap($user, $room);
        return Common::apiResponse(1, __('api_responses.Success_on_the_mic'));
    }

    public function goMicrophone(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->goMic($data);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        if ($room) {
            UserHandling::calcTime($data['user_id']);
            // Charisma is client-side seat state now; it resets on the client when
            // the holder leaves the seat — no backend cleanup needed here.
            return Common::apiResponse(1, __('api_responses.success'));
        } else {
            return Common::apiResponse(0, __('api_responses.failed'), null, 400);
        }
    }

    public function goMicrophone2(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->goMic2($data);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        if ($room) {
            UserHandling::calcTime($data['user_id']);
            // Charisma is client-side seat state now; it resets on the client when
            // the holder leaves the seat — no backend cleanup needed here.
            return Common::apiResponse(1, __('api_responses.success'));
        } else {
            return Common::apiResponse(0, __('api_responses.failed'), null, 400);
        }
    }

    //mute mic place
    public function mute_microphone(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic($data, 'mute');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {
            $ms = [
                'messageContent' => [
                    'message' => 'muteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_locked_the_microphone_position'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_lock_microphone'), null, 400);
        }
    }

    public function mute_microphone2(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic2($data, 'mute');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {
            $ms = [
                'messageContent' => [
                    'message' => 'muteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_locked_the_microphone_position'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_lock_microphone'), null, 400);
        }
    }

    public function kickMicrophone(Request $request)
    {
        return $this->microphoneService->kickMicrophone($request);
    }

    //unmute mic place
    public function unmute_microphone(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic($data, 'unmute');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {

            $ms = [
                'messageContent' => [
                    'message' => 'unmuteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    public function unmute_microphone2(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic2($data, 'unmute');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {

            $ms = [
                'messageContent' => [
                    'message' => 'unmuteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    //lock mic place
    public function shut_microphone(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic($data, 'shut');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if ($room) {
            $ms = [
                'messageContent' => [
                    'message' => 'lockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    public function shut_microphone2(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic2($data, 'shut');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if ($room) {
            $ms = [
                'messageContent' => [
                    'message' => 'lockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    //open mic place
    public function open_microphone(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic($data, 'open');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {
            $room = Room::query()->where('uid', $data['owner_id'])->first();
            $ms = [
                'messageContent' => [
                    'message' => 'unLockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    public function open_microphone2(Request $request)
    {
        $data = $request;
        try {
            $room = $this->microphoneService->mic2($data, 'open');
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if (true) {
            $room = Room::query()->where('uid', $data['owner_id'])->first();
            $ms = [
                'messageContent' => [
                    'message' => 'unLockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('api_responses.Successfully_unlocked_the_microphone'));
        } else {
            return Common::apiResponse(0, __('api_responses.Failed_to_unlock_microphone'), null, 400);
        }
    }

    public function calcTime($uid)
    {

        // case 1 : up_mic and go_mic in the same day
        $user  = User::find($uid);
        $timer =
            LiveTime::query()->where('uid', $uid)->whereDate('created_at', today())->where('end_time', null)->orderByDesc('id')->first();

        if ($timer) {
            $hours           = round((time() - $timer->start_time) / (60 * 60), 2);
            $timer->end_time = time();
            $timer->hours    = $hours;
            $timer->save();

            $user_hours =
                LiveTime::query()->where('uid', $user->id)->whereYear('created_at', '=', Carbon::now()->year)->whereMonth('created_at', '=', Carbon::now()->month)->whereDay('created_at', '=', Carbon::now()->day)->sum('hours');


            $hours = (int)$user_hours;

            if ($hours >= 1 && $user->today_days == 0) {
                DB::statement("
                UPDATE users
                SET today_days = 1
                WHERE id = :id
            ", ['id' => $user->id]);
            }
        }
    }

    public function mute_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'owner_id'          => 'required',
            'muted_id'          => 'required'
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->getMessages() as $message) {
                $error = implode($message);
                $errors[] = $error;
            }
            return Common::apiResponse(0, implode(' , ', $errors), null, 400);
        }
        $enter_room = EnteredRoom::query()
            ->where('ruid', $request->input('owner_id'))
            ->first();
        if ($enter_room) {
            $muter_user = Auth::user();
            $owner_id = $request->owner_id;
            $muted_user = User::find($request->input('muted_id'));
            if (!$muted_user) {
                return Common::apiResponse(0, __('api_responses.u_not_in_fund'), null, 404);
            }

            $in_room = EnteredRoom::query()
                ->where('ruid', $request->input('owner_id'))
                ->where('uid', $muted_user->id)
                ->first();

            if (!$in_room) {
                return Common::apiResponse(0, __('api_responses.u_not_in_fund'), null, 404);
            }

            // $room = $enter_room->room;

            $room = Room::query()->where('uid', $request->owner_id)->first();
            if (!$room) return Common::apiResponse(0, __('api_responses.room_not_found'), null, 404);

            $room_admin = explode(',', $room->room_admin);

            // Use repository for visitor operations
            $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
            $isVisitor = $visitorRepo->isVisitor($room->id, $muted_user->id);
            $isAdmin = in_array($muted_user->id, $room_admin);
            $isOwner = $request->owner_id === $muter_user->id;

            if (!$isVisitor && !$isAdmin && !$isOwner) {
                return Common::apiResponse(0, __('api_responses.u_not_in_room'), null, 404);
            }

            // case 1 : muter is host
            //########## case 1 - 1 : muter will mute himself
            // TODO: code here to mute himself
            //########## case 1 - 2 : muter will mute admin or user
            // TODO: code here to mute user or admin if not muted

            if ($request->owner_id  ==  $muter_user->id) {


                if ($muted_user->id == $muter_user->id) {
                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 1
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_mute'), null, 200);
                }


                $muted =  EnteredRoom::where('uid', @$muted_user->id)->update([
                    'muted_by_admin' => 1,
                    'muted_by_himself' => 1

                ]);

                if (!$muted) {
                    return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                }
                return Common::apiResponse(1, __('api_responses.sases_mute'), null, 200);
            }



            // case 2 : muter is admin
            if (in_array($muter_user->id, $room_admin)) {

                //########## case 2 - 1 : muter will mute host
                // TODO: can't mute host
                if ($muted_user->id == $owner_id) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }

                //########## case 2 - 2 : muter will mute admin
                // TODO: can't mute admin


                //########## case 2 - 3 : muter will mute himself
                // TODO: code here to mute himself

                if ($muted_user->id == $muter_user->id) {
                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 1
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_mute'), null, 200);
                }

                if (in_array($muted_user->id, $room_admin)) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }

                //########## case 2 - 4 : muter will mute user
                // TODO: code here to mute user if not muted

                $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                    'muted_by_admin' => 1,
                    'muted_by_himself' => 1

                ]);

                if (!$muted) {
                    return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                }
                return Common::apiResponse(1, __('api_responses.sases_mute'), null, 200);
            }



            // case 3 : muter is user (visitor)
            // Check if muter is a regular visitor (not owner, not admin)
            $isMuterVisitor = $visitorRepo->isVisitor($room->id, $muter_user->id) &&
                              !in_array($muter_user->id, $room_admin) &&
                              $request->owner_id !== $muter_user->id;

            if ($isMuterVisitor) {
                //########## case 2 - 1 : muter will mute host
                // TODO: can't mute host


                if ($muted_user->id == $owner_id) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }
                //########## case 2 - 2 : muter will mute admin
                // TODO: can't mute admin

                elseif (in_array($muted_user->id, $room_admin)) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }
                //########## case 2 - 3 : muter will mute himself
                // TODO: code here to mute himself if not muted
                elseif ($muted_user->id == $muter_user->id) {

                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 1
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_mute'), null, 200);
                }


                return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
            }





            return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
            //dd('');
        } else {
            return Common::apiResponse(0, __('api_responses.room_not_found'), null, 404);
        }
    }

    public function unmute_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'owner_id'          => 'required',
            'muted_id'          => 'required'
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->getMessages() as $message) {
                $error = implode($message);
                $errors[] = $error;
            }

            return Common::apiResponse(0, implode(' , ', $errors), null, 400);
        }


        $enter_room = EnteredRoom::query()
            ->where('ruid', $request->input('owner_id'))
            ->first();
        if ($enter_room) {
            $muter_user = Auth::user();
            $owner_id = $request->owner_id;
            $muted_user = User::find($request->input('muted_id'));
            $room = $enter_room->room;

            $room = Room::query()->where('uid', $request->owner_id)->first();
            if (!$room) return Common::apiResponse(0, __('api_responses.room_not_found'), null, 404);

            $room_admin = explode(',', $room->room_admin);

            // Initialize repository for visitor operations
            $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);

            $in_room = EnteredRoom::query()
                ->where('ruid', $request->input('owner_id'))
                ->where('uid', $muted_user->id)
                ->first();

            if (!$in_room) {
                return Common::apiResponse(0, __('api_responses.u_not_in_fund'), null, 404);
            }

            // case 1 : muter is host
            //########## case 1 - 1 : muter will mute himself
            // TODO: code here to mute himself
            //########## case 1 - 2 : muter will mute admin or user
            // TODO: code here to mute user or admin if not muted

            if ($request->owner_id  ==  $muter_user->id) {

                if ($muted_user->id == $muter_user->id) {
                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 0
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_un_mute'), null, 200);
                }

                $muted =  EnteredRoom::where('uid', @$muted_user->id)->update([
                    'muted_by_admin' => 0
                ]);

                if (!$muted) {
                    return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                }
                return Common::apiResponse(1, __('api_responses.sases_un_mute'), null, 200);
            }



            // case 2 : muter is admin
            if (in_array($muter_user->id, $room_admin)) {

                //########## case 2 - 1 : muter will mute host
                // TODO: can't mute host
                if ($muted_user->id == $owner_id) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }

                //########## case 2 - 2 : muter will mute admin
                // TODO: can't mute admin


                //########## case 2 - 3 : muter will mute himself
                // TODO: code here to mute himself

                if ($muted_user->id == $muter_user->id) {
                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 0
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_un_mute'), null, 200);
                }

                if (in_array($muted_user->id, $room_admin)) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }

                //########## case 2 - 4 : muter will mute user
                // TODO: code here to mute user if not muted

                $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                    'muted_by_admin' => 0
                ]);

                if (!$muted) {
                    return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                }
                return Common::apiResponse(1, __('api_responses.sases_un_mute'), null, 200);
            }



            // case 3 : muter is user (visitor)
            // Check if muter is a regular visitor (not owner, not admin)
            $isMuterVisitor = $visitorRepo->isVisitor($room->id, $muter_user->id) &&
                              !in_array($muter_user->id, $room_admin) &&
                              $request->owner_id !== $muter_user->id;

            if ($isMuterVisitor) {
                //########## case 2 - 1 : muter will mute host
                // TODO: can't mute host

                if ($muted_user->id == $owner_id) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }
                //########## case 2 - 2 : muter will mute admin
                // TODO: can't mute admin

                elseif (in_array($muted_user->id, $room_admin)) {
                    return Common::apiResponse(0, __('api_responses.you_cant_mute_it'), null, 404);
                }
                //########## case 2 - 3 : muter will mute himself
                // TODO: code here to mute himself if not muted
                elseif ($muted_user->id == $muter_user->id) {

                    $muted =  EnteredRoom::where('uid', $muted_user->id)->update([
                        'muted_by_himself' => 0
                    ]);

                    if (!$muted) {
                        return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
                    }
                    return Common::apiResponse(1, __('api_responses.sases_un_mute'), null, 200);
                }

                return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
            }

            return Common::apiResponse(0, __('api_responses.try_agane_leter'), null, 404);
        } else {
            return Common::apiResponse(0, __('api_responses.room_not_found'), null, 404);
        }
    }

    public function lifeTime(Request $request)
    {
        $sec = $request->input('sec');
        $user = $request->user();

        [$hours, $totalTime] = $this->microphoneService->createLiveTime($user->id, $sec);

        if ($hours >= 1 && $user->today_days == 0) {
            DB::statement("
                UPDATE users
                SET today_days = 1
                WHERE id = :id
            ", ['id' => $user->id]);
        }
        return Common::apiResponse(1, __('api_responses.liveTime') . $totalTime . ' ' . __('api_responses.hours'), null, 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Modules\Vip\Entities\Vip;
use App\Models\Room;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use App\Classes\Packs\AllowPacks;
use App\Tik\Services\HomeService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{


    public function __construct(private HomeService $homeService) {}


    public function one_page(Request $request)
    {
        $type = $request->type;
        if (!$type)  return Common::apiResponse(0, 'Missing parameters', null, 422);
        $data = DB::table('pages')->where(['type' => $type])->get();
        return Common::apiResponse(1, '', $data);
    }

    public function countVipsold()
    {
        $count = Vip::query()->where('type', 3)->count();
        $vips =  Vip::query()->where('type', 3)->select('id', 'level', 'type', 'img')->get();
        return Common::apiResponse(1, '', ['vip_count' => $count, 'vips' => $vips]);
    }


    public function getTimes(Request $request)
    {

        $userId = $request->user_id ?: Auth::id();


        [$user, $diamonds, $days, $type, $totalTime, $today] = $this->homeService->totalHours($request, $userId);

        return Common::apiResponse(
            1,
            '',
            [
                'diamonds' => (int)$diamonds ?: 0,
                'days'     => (int)$days ?: 0,
                'hours'    => $totalTime ?: 0,
                'today'    => $today,
                'user_extras' => UserCommon::UserStatistic($user->id, $type, true) ?? new \stdClass(),
            ],
            200
        );
    }


    public function openTicket(Request $request)
    {

        $request->validate([
            'contact' => 'required|string',
            'txt' => 'required|string|min:10|max:500',
        ], [
            'contact.required' => 'حقل الاتصال مطلوب.',
            'txt.required' => 'حقل النص مطلوب.',
            'txt.min' => 'يجب أن يكون النص على الأقل 10 حروف.',
            'txt.max' => 'لا يمكن أن يزيد النص عن 500 حرف.',
        ]);

        if (!$request->contact || !$request->txt) {
            return Common::apiResponse(0, 'missing params');
        }
        try {
            $tkt = $this->homeService->openTicket($request);
            $out = [
                'contact' => $tkt->contact_num,
                'txt' => $tkt->problem,
                'description' => $tkt->description,
                'image' => $tkt->img,
            ];
            return Common::apiResponse(1, 'done', $out, 200);
        } catch (\Throwable $th) {

            return Common::apiResponse(0, $th->getMessage());
        }
    }

    public function sendToStream(Request $request)
    {
        $ms = [
            'messageContent' => [
                'message' => $request->message,
            ]
        ];
        $ex = json_decode($request->ext, true);
        if (is_array($ex)) {
            foreach ($ex as $k => $value) {
                $ms['messageContent'][$k] = $value;
            }
        }

        $json = json_encode($ms);
        $user_id = $request->user_id ?: 0;
        $action = $request->action ?: 'SendCustomCommand';
        $room = Room::query()->where('uid', $request->owner_id)->first();
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);
        Common::sendToStream($action, $room->id, $user_id, $json);
        return Common::apiResponse(1, 'done', null, 201);
    }


    public function getImages()
    {
        $type = \request()->type ?? '';
        if ($type == '2') {
            $data = $this->getGamesImages();
            return Common::apiResponse(1, 'ok', $data, 200);
        }


        [$pk_images, $vip_images] =  $this->homeService->imageIndex();
        $data = [
            'pk_images' => $pk_images,
            'vip_images' => $vip_images,
        ];
        return Common::apiResponse(1, 'ok', $data, 200);
    }

    public function check_wapel(Request $request)
    {

        $user = $request->user();
        $user->loadMissing('profile');

        try {
            [$level, $expire, $ware, $roomId, $wapel] =   $this->homeService->wapel($user->id, $request->owner_id);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        if ($wapel) {

            $ms = [
                'messageContent' => [
                    'msg' => 'PobUp',
                    'uId' => $user->id,
                    //'num'=>$ware->use_num,
                    'my_msg' => $request->message,

                    'name' => $user->name,
                    'image' => $user->profile?->avatar,

                    'VIP' => $level,
                    'check_wapel' => $expire,
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', @$roomId, $user->id, $json);

            return Common::apiResponse(1, 'has wapel', @$ware, 200);
        } else {
            return Common::apiResponse(0, 'no wapel', null, 404);
        }
    }



    public function getUserHides(Request $request)
    {
        $user         = $request->user();
        $privilegeArr = [
            'has_color_name' => 18,
            //            'anonymous'      => 17,
            'country'        => 13,
            'last_active'    => 20,
            'visit'          => 19,
            'room'           => 16,
            'sound_effect'    => 21
        ];
        $packsClass   = new AllowPacks($user, $privilegeArr);
        $data = $packsClass->getData();

        return Common::apiResponse(1, 'ok', $data, 200);
    }




    /**
     * @param mixed $user_hours
     * @return string
     */
    public function timeDoubleToString(mixed $user_hours): string
    {

        $user_hours    = (float)($user_hours * 100 / 60);
        $intHours      = (int)$user_hours;
        $intMinutes    = (int)(($user_hours - $intHours) * 100);
        if ($intMinutes >= 60) {
            $intMinutes -= 60;
            $intHours += 1;
        }
        return sprintf('%02d:%02d:00', $intHours, $intMinutes);
    }

    /**
     * @return array[]
     */
    private function getGamesImages(): array
    {
//        settings()->set('images_updated_at', time());
        return [
            'dice'     => [
                'id'    => 1,
                'image' => 'extradata/room_default_dice.svga'
            ],
            'rps'      => [
                'id'    => 2,
                'image' => 'extradata/room_finger_guessing.svga'
            ],
            'gift_box' => [
                'id'    => 3,
                'image' => 'extradata/Icon_G_Box.svga'
            ]
        ];
    }

    public function check_if_friend(Request $request)
    {
        $me = $request->user();
        $user_id = $request->user_id;
        if (in_array($user_id, $me->friends_ids()->toArray())) {
            return Common::apiResponse(1, 'exists', true);
        }
        return Common::apiResponse(1, 'does not exists', false);
    }

    public function hide(Request $request)
    {
        try {
            $user         = $request->user();
            $privilegeArr = [
                'has_color_name' => 18,
                // 'anonymous'      => 17,
                'country'        => 13,
                'last_active'    => 20,
                'visit'          => 19,
                'room'           => 16,
                'sound_effect'   => 21,
                'being_kicked'   => 29,
                'anti_ban'       => 30,
            ];
            $type         = $request->type;

            $this->homeService->changePackMode($type, $privilegeArr, $user, true);

            return Common::apiResponse(1, 'ok', null, 200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }

    public function un_hide(Request $request)
    {
        try {
            $user = $request->user();
            $privilegeArr = [
                'has_color_name' => 18,
                //            'anonymous'      => 17,
                'country'        => 13,
                'last_active'    => 20,
                'visit'          => 19,
                'room'           => 16,
                // 'spechEfeect'    => 22
                'sound_effect'   => 21,
                'being_kicked'   => 29,
                'anti_ban'       => 30,
            ];
            $type         = $request->type;
            $this->homeService->changePackMode($type, $privilegeArr, $user, false);

            return Common::apiResponse(1, 'ok', null, 200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }

    // public function changePackMode($type, $privilegeArr, User $user, $isAvailable)
    // {
    //     if (key_exists($type, $privilegeArr)) {
    //         $privilegeId = $privilegeArr[$type];

    //         if ($isAvailable && !Ware::query()->where('type', $privilegeId)->exists()) {
    //             return Common::apiResponse(0, 'not found', null, 404);
    //         } else if (!Pack::query()->where('user_id', $user->id)->where('type', $privilegeId)->where('is_used', !$isAvailable)->exists()) {

    //             return Common::apiResponse(0, 'not allowed', null, 403);
    //         }

    //         Pack::query()->where('user_id', $user->id)->where('type', $privilegeId)->update(['is_used' => $isAvailable]);

    //         switch ($type) {
    //             case 'country':
    //                 if ($isAvailable) {
    //                     $user->country_id = null;
    //                     $user->save();
    //                 }
    //                 break;
    //             case 'room':
    //                 Room::query()->where('uid', $user->id)->update(['room_status' => $isAvailable ? 2 : 1]);
    //                 break;
    //         }
    //     }
    // }


}

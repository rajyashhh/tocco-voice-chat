<?php

namespace Modules\Moment\Http\Controllers;

use App\Enums\UserDiamondLogType;
use App\Helpers\UserDiamondLogHelper;
use DB;
use App\Models\Gift;
use App\Models\User;
use App\Helpers\Common;
use App\Models\GiftLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Facades\CustomNotification;
use Modules\Moment\Entities\Moment;

use App\Exceptions\NotInfMoneyException;
use Modules\Moment\Entities\MomentUserGift;
use Illuminate\Contracts\Support\Renderable;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use Modules\Moment\Transformers\MomentGiftUserResource;


class MomentUserGiftsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index($momentId)
    {
        //        Moment::query()->where('id')

    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('moment::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request, $moment_id, UpdateUserWhenSendGift $updateUserWhenSendGift)
    {
        $data    = $request;
        $user    = $request->user();
        // $userId  = $user->id;
        $moment_id = $moment_id;
        $giftId  = $data['gift_id'];
        $number  = $data['num'];
        $moment = Moment::find($moment_id);
        // Validation if moment return null
        if (!$moment) return Common::apiResponse(0, 'Moment does not exist or has been removed', null, 404);

        //validation parameter
        if (!$data['gift_id']  || !$data['num'])
            return Common::apiResponse(0, __('missing params'), $data->all());

        //validation if pass num < 1
        if ($data['num'] < 1) return Common::apiResponse(0, 'The number of gifts cannot be less than 1', null, 422);



        //get the gift data from id in the parameter
        $gift = Gift::query()->select([
            'id',
            'name',
            'type',
            'price',
            'vip_level',
            'is_play',
            'img',
            'show_img',
            'show_img2'
        ])->where('id', $giftId)->where('enable', 1)->first();
        // Validation if gift return null
        if (!$gift) return Common::apiResponse(0, 'Gift does not exist or has been removed', null, 404);
        // attached moments and gifts
        $moment->gifts()->attach($gift, ['user_id' => $user->id, 'num' => $number]);
        // receivers ids
        $receiversIds = explode(',', $data['toUid']);
        $numberOfGift = $number * count($receiversIds);

        $totalPrice = $gift->price * $numberOfGift;

        // if user didn't have inf coins throw exception
        if ($user->di < $totalPrice) return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);

        // validation if this gift vip < user vip then throw Exception
        $vip_level = @Common::ovip_center($user);
        if (@$vip_level->level < $gift->vip_level) return Common::apiResponse(0, 'vip ' . $gift->vip_level . ' to send this gift');

        try {
            $updateUserWhenSendGift->send($totalPrice, $user);
        } catch (NotInfMoneyException $e) {
            return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);
        }

        // get received users data
        $receivedUsers = $moment->user;
        // User::withoutAppends()->with(['agency', 'profile'])->whereIn('id', $receiversIds)->get();
        $to    = $receivedUsers->name;
        $fromName = $user->name;

        $this->sendGift($number, $moment_id, $gift, $user, $receivedUsers);
        $price = $number * $gift->price;
        $updateUserWhenSendGift->update($price, $receivedUsers);

           UserDiamondLogHelper::logByType(
            $receivedUsers->id,
            $price,
            $receivedUsers->monthly_diamond_received,
            UserDiamondLogType::MOMENT,
            $user->id,


        );
        return Common::apiResponse(1, "  {$number} x ارسل هدية  " . " قيمتها {$gift->price} " . " الى {$to}");
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('moment::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('moment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }


    public function sendGift($number, $momentId, Gift $gift, User $senderUser, $receivedUser,  $isPlay = 0, $totalPrice = null)
    {
        if ($totalPrice == null) $totalPrice = $gift->price * $number;

        $info['giftId']       = $gift->id;
        $info['roomowner_id'] = 0;
        $info['giftNum']      = $number;
        $info['giftName']     = $gift->name ?: '_';
        $info['giftPrice']    = $totalPrice;
        $info['app_profit_coins']    = $totalPrice;
        $info['sender_id']    = $senderUser->id;
        $info['receiver_id']  = $receivedUser->id;
        $info['is_play']      = $isPlay ? 2 : 1;
        $info['type']         = 2;
        $info['moent_id']         = $momentId;
        $info['created_at']   = $info['updated_at'] = date('Y-m-d H:i:s', time());

        // $income = $this->calculate($room->uid,$senderUser->id,$info['giftPrice']);
        // $info['platform_obtain']=$income['platform'];   //platform
        // $info['receiver_obtain']=$income['toUid'];     //recipient
        // $info['roomowner_obtain']=$income['uid']+$income['uid_yj'];//homeowner

        // $info['agency_id']=$income['uid']+$income['uid_yj'];//homeowner


        GiftLog::query()->create($info);
        CustomNotification::sendMomentGift($senderUser, $gift, $receivedUser, $momentId);
    }

    public function getGifts($id)
    {

        $moment = Moment::with('gifts')->find($id);
        if (!$moment) {
            return Common::apiResponse(0, 'Moment does not exist or has been removed', null, 404);
        }
        $data = $moment->gifts()->select('gifts.img', DB::raw('CAST(sum(moment_user_gifts.num) AS INT) as num_gift'))
            ->groupBy('gifts.id', 'gifts.img', 'moment_user_gifts.moment_id', 'moment_user_gifts.gift_id')->orderByDesc('num_gift')
            ->get();

        return Common::apiResponse(1, 'successful', $data, 200);
    }

    public function userGift($id)
    {
        // whereHas('user'): rows whose gifting user was deleted are excluded from the
        // result — the resource calls Common::level_center($this->user), which crashed
        // with "receiverLevel on null" (CalcsTrait:496) when user was null.
        // Level relations are eager-loaded so level_center reads them without N+1.
        $momentsGift = MomentUserGift::selectRaw('user_id, moment_id, SUM(num) as num')
            ->where('moment_id', $id)
            ->whereHas('user')
            ->groupBy('user_id', 'moment_id')
            ->with(['user.profile', 'user.receiverLevel', 'user.senderLevel'])
            ->get();

        return Common::apiResponse(1, 'successful', MomentGiftUserResource::collection($momentsGift), 200);
    }
}

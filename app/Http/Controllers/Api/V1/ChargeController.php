<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ShippingAgencyHelper;
use Exception;
use App\Models\User;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Http\Controllers\Controller;
use App\Tik\Services\ChargeRepoService;
use App\Http\Resources\Api\V1\TrxResource;
use App\Http\Resources\Api\V1\ChargeResource;
use App\Http\Resources\DollarChargeLogResource;
use App\Http\Resources\DollarChargeAgencyResource;
use Modules\SalaryTransaction\Entities\ChargeAgency;
use App\Http\Resources\Api\V1\ChargeRecievedInfoResource;
use App\Http\Resources\Api\V1\ChargeResourceforAgencyCharge;
use Illuminate\Support\Facades\Log;
use Modules\Achievement\Http\Services\UserAchievementService;


class ChargeController extends Controller
{

    protected $chargeService;

    public function __construct(ChargeRepoService $chargeService)
    {
        $this->chargeService = $chargeService;
    }


    public function charge_co_for_owner(Request $request)
    {
        //done
        $user = $request->user();
        $count = $request->amount;
        $userUuid = $request->id;
        //        if ($user->charge_status == 0) {
        //            return Common::apiResponse(0, __('api.freez_charge'), 404);
        //        }
        if ($count < 0 || !is_numeric($count)) {
            return Common::apiResponse(0, 'this value not allow', 422);
        }
        if (!$userUuid || !$count) {
            return Common::apiResponse(0, __('api_responses.missing_params'), 404);
        }
        try {
            $this->chargeService->chargeCoinsFromOwner($count, $user->id, $userUuid);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }


        return Common::apiResponse(true, 'Your recharge was successful');
    }


    public function chargeTo(Request $request)
    {
        $app_feature = \Cache::get('host_agency');
        if (!$app_feature) {
            throw new Exception(__('Agency Feature is Disabled, Contact the administration'));
        }

        $limitWithdrawal = (int) (Common::getSettingValue('limit_daily_withdrawal') ?? 1);

        if(floatval($request->usd) < $limitWithdrawal){
            return Common::apiResponse(0, __('api_responses.min_withdrawal_amount', ['amount' => $limitWithdrawal]), 422);
        }

        $types = [
            'user' => [$this, 'chargeToUser'],
            'agency' => [$this, 'chargeToAgency']
        ];

        $type = $request->input('type');
        $instance = $types[$type] ?? $types['agency'];

        if (!$instance) {
            return Common::apiResponse(0, 'Type Not Found', 400);
        }
        try {
            $data = call_user_func($instance, $request);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 400);
        }
        return $data;
    }

    public function chargeToUser(Request $request)
    {
        $stop_all_charge = Common::stopSwitch('stop_charge') ? 1 : 0;
        if ($stop_all_charge === 1) {
            return Common::apiResponse(0, __('api_responses.freeze_charge_settings'), 404);
        }
        $toId = $request->to_id;
        $from = $request->user();
        $isRoomTarget = false;
        $to = User::find($toId);
        if (!$to) return Common::apiResponse(0, __('user not found'), 400);

        if ($from->id == $to->id) {
            $charge_user_to_self = (int) (Common::getSettingValue('charge_user_to_self') ?? 1);
            if ($charge_user_to_self !== 1) {
                return Common::apiResponse(0, __('api_responses.charge_to_self_disabled'), 403);
            }
        } else {
            $charge_user_to_user = (int) (Common::getSettingValue('charge_user_to_user') ?? 1);
            if ($charge_user_to_user !== 1) {
                return Common::apiResponse(0, __('api_responses.charge_user_to_user_disabled'), 403);
            }
        }

        if ($from->transfer_salary == 1) {
            return Common::apiResponse(0, __('api_responses.freeze_transfer_charger'), 404);
        }

        // Common::checkUserAgencyFrozen($from);

        if ($to->transfer_salary == 1) {
            return Common::apiResponse(0, __('api_responses.freeze_transfer_receiver'), 404);
        }

        $usd = floatval($request->usd);

        if ($usd <= 0) {
            return Common::apiResponse(0, 'This value is not allowed', 422);
        }

        if (!$usd)  return Common::apiResponse(0, 'not found', 404);

        $rate = Common::getCoinsValue('user_coins');

        if (!$rate)  return Common::apiResponse(0, 'please set usd_value_in_coins in configs', 422);

        $coins = $usd * $rate;

        $totalSalary = $from->salary;
        $roomSalary = $from->ownerRoom?->salary;
        if ($totalSalary < $usd) {
            return Common::apiResponse(0, 'balance not enough', 407);
        } else if ($roomSalary >= $usd) {
            $isRoomTarget = (bool)$from->ownerRoom;
        }
        DB::beginTransaction();
        try {

            $this->chargeService->chargeTo($from, $to, $coins, $isRoomTarget, $usd);
            $data = ['coins' => (string)$from->di, 'usd' => (string)$from->salary,];

            DB::commit();

            $title = 'Coins Received';
            $body = 'You have received :coins coins (equivalent to :usd USD) from :sender.';

            CustomNotification::charges(
                $to,
                $title,
                $body,
                ['coins' => $coins, 'usd' => $usd, 'sender' => $from->name],
            );

            return Common::apiResponse(1, 'success', $data, 201);
        } catch (Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), 400);
        }
    }




    public function chargeToAgency(Request $request)
    {
        $stop_all_charge = Common::stopSwitch('stop_charge') ? 1 : 0;
        if ($stop_all_charge === 1) {
            return Common::apiResponse(0, __('api_responses.freez_charge'), 404);
        }

        // Check if user to charging agent transfer is enabled
        $charge_user_to_agent = (int) (Common::getSettingValue('charge_user_to_agent') ?? 1);
        if ($charge_user_to_agent !== 1) {
            return Common::apiResponse(0, __('api_responses.charge_user_to_agent_disabled'), 403);
        }

        $toId = $request->to_id;
        $from = $request->user();
        $isRoomTarget = false;

        if ($from->transfer_salary == 1) {
            return Common::apiResponse(0, __('api_responses.freeze_transfer_charger'), 404);
        }
        Log::info("ChargeToAgency: User {$from->id} is trying to charge agency {$toId} with amount {$request->amount}");
        Common::checkUserAgencyFrozen($from);
        Log::info("ChargeToAgency: User {$from->id} passed agency frozen check");
        $to = Common::searchAgency($toId);
        if (!$to) return Common::apiResponse(0, 'Not allowed To this agency or this not an agency', 422);

        if (!ShippingAgencyHelper::isVerifiedChargeForAgency($to)) {
            return Common::apiResponse(0, __('not_verified_agency'), 403);
        }

        if ($to->is_frozen == 1) {
            return Common::apiResponse(0, __('api_responses.frozen_agency'), 404);
        }



        $usd = floatval($request->usd);

        if ($usd <= 0) {
            return Common::apiResponse(0, 'This value is not allowed', 422);
        }

        if (!$usd || !$to) {
            return Common::apiResponse(0, 'not found', 404);
        }
        $rate = Common::getCoinsValue('shipping_coins');

        if (!$rate) {
            return Common::apiResponse(0, 'please set usd_value_in_coins in configs', 422);
        }
        $coins = $usd * $rate;
        $totalSalary = $from->salary;
        $roomSalary = $from->ownerRoom?->salary;
        if ($totalSalary < $usd) {
            return Common::apiResponse(0, 'balance not enough', 407);
        } else if ($roomSalary >= $usd) {
            $isRoomTarget = (bool)$from->ownerRoom;
        }
        DB::beginTransaction();
        try {
            $this->chargeService->chargeToAgency($from, $to, $coins, $isRoomTarget, $usd);

            $data = ['coins' => (string)$from->di, 'usd' => (string)$from->salary,];
            CustomNotification::hostSalary($to, $from,  $usd);
            DB::commit();
            return Common::apiResponse(1, 'success', $data, 201);
        } catch (Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), 400);
        }
    }



    public function sendMoneyFoeHost(Request $request)
    {
        //done
        //        $stop_all_charge = settings()->get("stop_charge") ? settings()->get("stop_charge") : 0;
        //        if ($stop_all_charge == 1) {
        //            return Common::apiResponse(0, __('api.freez_charge'), 404);
        //        }

        $user = $request->user();

        if ($user->is_bd) return Common::apiResponse(false, 'You are BD, You can\'t charge', null, 407);

        Common::checkUserAgencyFrozen($user);

        $count = $request->amount;
        $userUuid = $request->user_id;

        if ($count < 0 || !is_numeric($count)) {
            return Common::apiResponse(0, 'this value not allow', 422);
        }

        if ($user->di < $count) {
            return Common::apiResponse(0, 'balance not enough');
        }

        try {
            [$userReceiver, $chargeId] = $this->chargeService->sendMoney($user, $userUuid, $count);
            if ($userReceiver instanceof User) {
                (new UserAchievementService())->insertCharging($userReceiver, $count);
            }
            UserCommon::UserEarnedInvitation($userReceiver->id, $count, $chargeId);
            $data = ['coins' => (string)$user->di, 'usd' => (string)$user->salary,];
            return Common::apiResponse(1, 'your recharge was successful', $data, 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage());
        }
    }

    public function chargeCoForUsersHistory(Request $request)
    {
        $userId = $request->user()->id;
        if (!$request->type) return Common::apiResponse(0, 'missing params', null, 422);
        $charge = $this->chargeService->getChargeUserHistory(userId: $userId, type: $request->type, chargeType: 'agency');
        return Common::apiResponse(1, '', ChargeResourceforAgencyCharge::collection($charge), 200);
    }


    public function ChargeDollarForOwner(Request $request)
    {
        $app_feature = \Cache::get('host_agency');
        if (!$app_feature) {
            throw new Exception(__('Agency Feature is Disabled, Contact the administration'));
        }
        $stop_all_charge = Common::stopSwitch('stop_charge') ? 1 : 0;
        if ($stop_all_charge === 1) {
            return Common::apiResponse(0, __('api_responses.freeze_charge_settings'), 404);
        }

        $limitWithdrawal = (int) (Common::getSettingValue('limit_daily_withdrawal') ?? 1);

        if($request->amount < $limitWithdrawal){
            return Common::apiResponse(0, __('api_responses.min_withdrawal_amount', ['amount' => $limitWithdrawal]), 422);
        }

         
        $types = [
            'user' => [$this, 'ChargeDollarForOwner_to_users'],
            'agency' => [$this, 'ChargeDollarForOwner_to_agency']
        ];

        $type = $request->input('type');
        $instance = $types[$type] ?? $types['user'];
        if (!$instance) {
            return Common::apiResponse(0, 'Type Not Found', 400);
        }

        $data = call_user_func($instance, $request);
        return $data;
    }


    public function ChargeDollarForOwner_to_users(Request $request)
    {
        //done
        //        return Common::apiResponse(0, 'try again');
        $user = $request->user();
        if ($user->transfer_salary == 1) return Common::apiResponse(false, __('Transfer salary has been disabled!'), null, 407);
        if ($user->is_bd) return Common::apiResponse(false, 'You are BD, You can\'t charge', null, 407);

        Common::checkUserAgencyFrozen($user);

        $count = $request->amount;
        $userUuid = $request->id;
        // if ($user->transfer_salary == 1) {
        //     return Common::apiResponse(0, __('api.freez_charge'), 404);
        // }
        if ($count < 0 || !is_numeric($count)) {
            return Common::apiResponse(0, 'this value not allow', 422);
        }
        if (!$userUuid || !$count) {
            return Common::apiResponse(0, __('api_responses.missing_params'), 404);
        }

        try {
            [$receiver, $amount, $salary, $chargeId] = $this->chargeService->chargeDollarForOwner($user, $userUuid, $count);

            if ($user instanceof User) {
                (new UserAchievementService())->insertCharging($receiver, $amount);
            }
            UserCommon::UserEarnedInvitation($receiver->id, $amount, $chargeId);
            UserCommon::addChargeLevel($receiver->id, $amount);
            $data = ['coins' => (string)$user->di, 'usd' => (string)$salary,];

            $title = 'Balance Recharged';
            $body = 'Your balance has been recharged with :usd coins by :name.';

            CustomNotification::charges(
                $receiver,
                $title,
                $body,
                ['usd' => $amount, 'name' => $user->name,]
            );

            return Common::apiResponse(1, 'Your recharge was successful', $data, 200);
        } catch (Exception $e) {

            return Common::apiResponse(0, $e->getMessage(), 400);
        }
    }


    public function ChargeDollarForOwner_to_agency(Request $request)
    {
        //done
        //        return Common::apiResponse(0, 'try again');
        $user = $request->user();
        if ($user->transfer_salary == 1) return Common::apiResponse(false, __('Transfer salary has been disabled!'), null, 407);
        if ($user->is_bd) return Common::apiResponse(false, 'You are BD, You can\'t charge', null, 407);

        Common::checkUserAgencyFrozen($user);

        $count = $request->amount;
        $userUuid = $request->id;
        // if ($user->transfer_salary == 1) {
        //     return Common::apiResponse(0, __('api.freez_charge'), 404);
        // }

        if ($count < 0 || !is_numeric($count)) {
            return Common::apiResponse(0, 'this value not allow', 422);
        }
        if (!$userUuid || !$count) {
            return Common::apiResponse(0, __('api_responses.missing_params'), 404);
        }
        $receiver = Common::searchAgency($userUuid);
        if ($receiver == false) return Common::apiResponse(0, 'this  not found', 422);

        if (!ShippingAgencyHelper::isVerifiedChargeForAgency($receiver)) {
            return Common::apiResponse(0, __('not_verified_agency'), 403);
        }

        if ($receiver->is_frozen == 1) {
            return Common::apiResponse(0, __('api_responses.frozen_agency'), 404);
        }



        try {
            [$receiver, $amount, $salary] = $this->chargeService->chargeDollarForOwner_to_agency($user, $userUuid, $count);

            $data = ['coins' => (string)$user->di, 'usd' => (string)$salary,];
            return Common::apiResponse(1, 'Your recharge was successful', $data, 200);
        } catch (Exception $e) {

            return Common::apiResponse(0, $e->getMessage(), 400);
        }
    }

    public function chargeDollarHistory(Request $request)
    {

        $userId = $request->user()->id;
        if (!$request->type) return Common::apiResponse(0, 'missing params', null, 422);
        $charge = $this->chargeService->getChargeUserHistory(userId: $userId, type: $request->type, chargeType: 'host_agency');
        return Common::apiResponse(1, '', ChargeResourceforAgencyCharge::collection($charge), 200);
    }

    public function chargeHistory(Request $request)
    {
        $userId = $request->user()->id;
        $charge = $this->chargeService->getChargeUserHistory($userId, $request->type, $request->by_date, 'host_agency');

        return Common::apiResponse(1, '', ChargeResource::collection($charge), 200);
    }


    public function userChargeCoins(Request $request)
    {
        $userId = $request->user()->id;
        $searchKey = $request->search_key ?? null;
        $charge = $this->chargeService->getChargeUserHistory($userId, 'received', null, 'host_agency');

        $charge = $charge->with(['sender.profile', 'sender.specialId.ware'])->when($searchKey, fn($query) => $query->whereHas('sender', fn($q) => $q->where('uuid', 'like', $searchKey)));
        if ($request->by_date) {
            $charge = $charge->where('created_at', 'like', "%$request->by_date%");
        }
        return Common::apiResponse(1, '', ChargeRecievedInfoResource::collection($charge->orderByDesc('created_at')->get()), 200);
    }

    public function userChargeCoinsII(Request $request)
    {
        $userId = $request->user()->id;
        $searchKey = $request->search_key ?? null;
        $charge = $this->chargeService->getChargeUserHistory($userId, 'received', $request->by_date, 'host_agency', $searchKey);

        return Common::apiResponse(1, '', ChargeRecievedInfoResource::collection($charge), 200);
    }


    public function trxLog(Request $request)
    {
        $user = $request->user();
        $searchKey = $request->search_key ?? null;
        $trx = $this->chargeService->getCoinLogs($user->id, $searchKey);
        return Common::apiResponse(1, '', TrxResource::collection($trx), 200);
    }


    public function getUserAgency(Request $request)
    {
        $data = $this->chargeService->userAgencySearch($request);
        return Common::apiResponse(1, '', $data, 200);
    }



    public function chargeFromAgencyToAnother(Request $request)
    {
        $from = $request->user();
        if (!$request->id || !$request->amount) return Common::apiResponse(false, 'missing_params');
        if ($request->amount < 0) return Common::apiResponse(false, 'value not allow');

        $toUser = null;
        $to = null;

        if ($request->type == 'user') {
            $toUser = User::find($request->id);
            if (!$toUser) {
                return Common::apiResponse(false, 'user_not_found');
            }
        }

        if ($request->type == 'agency') {
            $to = Common::searchAgency($request->id);
            if (!$to) {
                return Common::apiResponse(false, 'agency_not_found');
            }
        }

        if ($request->type == 'user') {
            $charge_agent_to_user = (int) (Common::getSettingValue('charge_agent_to_user') ?? 1);
            if ($charge_agent_to_user !== 1) {
                return Common::apiResponse(0, __('api_responses.charge_agent_to_user_disabled'), 403);
            }
        } elseif ($request->type == 'agency') {
            $charge_agent_to_agent = (int) (Common::getSettingValue('charge_agent_to_agent') ?? 1);
            if ($charge_agent_to_agent !== 1) {
                return Common::apiResponse(0, __('api_responses.charge_agent_to_agent_disabled'), 403);
            }
        }


        try {
            $this->chargeService->chargeAgencyToAnother($from, $request);

            return Common::apiResponse(1, 'your recharge was successful', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage());
        }
    }



    public function chargeToHistory(Request $request)
    {


        $types = [
            'user' => [$this, 'chargeToUserHistory'],
            'agency' => [$this, 'chargeToAgencyHistory']
        ];

        $type = $request->input('type');
        $instance = $types[$type] ?? $types['user'];

        if (!$instance) {
            return Common::apiResponse(0, 'Type Not Found', 400);
        }
        $data = call_user_func($instance, $request);
        return $data;
    }


    public function chargeToUserHistory($request)
    {
        $userId = auth()->user()->id;
        $charge = $this->chargeService->getChargeToUserHistory();
        return Common::apiResponse(1, '', DollarChargeLogResource::collection($charge), 200);
    }


    public function chargeToAgencyHistory($request)
    {

        $charge = $this->chargeService->getChargeAgencyHistory();
        return Common::apiResponse(1, '', DollarChargeAgencyResource::collection($charge), 200);
    }
}

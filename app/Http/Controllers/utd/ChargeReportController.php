<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\CoinGameUser;
use App\Models\CoinLog;
use App\Models\User;
use App\Models\UserLuckyGift;
use Illuminate\Http\Request;

class ChargeReportController extends Controller
{
    public function index(Request $request)
    {
        // Determine the type of report based on the request parameter
        $name = $request->query('name', 'result');

        switch ($name) {
            case 'stripe':
                $data = $this->stripeReport($request);
                break;
            case 'in-app-purchas':
                $data = $this->inAppPurchasReport($request);
                break;
            default:
                $data = $this->resultReport($request);
                break;
        }

        return Common::apiResponse(true, 'Success', $data);
    }

    public function return($id){
        $coinlog = CoinLog::where('id', $id)->first();
        if(!$coinlog){
            return Common::apiResponse(false, 'Coin Log Not Found');
        }
        $user = User::where('id', $coinlog->user_id)->first();
        if(!$user){
            return Common::apiResponse(false, 'User Not Found');
        }
        if ($coinlog->obtained_coins == 0) {
            return Common::apiResponse(false, 'Invalid coin amount');
        }
        if ($coinlog->obtained_coins > 0 && $user->di < $coinlog->obtained_coins) {
            return Common::apiResponse(false, 'Insufficient balance');
        }
        $user->di = $user->di - $coinlog->obtained_coins;
        $coinlog->obtained_coins = 0;
        $coinlog->delete();
        $user->update();
        return Common::apiResponse(true,'Success');
    }


    public function details(Request $request)
    {
        // Determine the current request name
        $isDashboard = $request->name == 'dash' || $request->name == null;
        $isApp = $request->name == 'app';
        $isStripe = $request->name == 'stripe';
        $isStripeNew = $request->name == 'stripenew';
        $isInApp = $request->name == 'in-app-purchas';

        // Anonymous function to get user based on UUID
        $getUserByUuid = function ($uuid) {
            return User::where('uuid', $uuid)->first();
        };

        // Function to calculate receiver value based on conditions
        $calculateReceiverValue = function ($user, $request) use ($isDashboard, $isApp, $isStripe, $isStripeNew, $isInApp) {
            if ($isDashboard || $isApp) {
                return Charge::where('user_id', $user?->id)->sum('amount');
            } elseif ($isStripe) {
                return CoinLog::where('user_id', $user?->id)
                    ->whereNotIn('method', ['huawei_pay', 'google_pay', 'strip', 'apple_pay'])
                    ->sum('obtained_coins');
            } elseif ($isStripeNew) {
                return CoinLog::where('user_id', $user?->id)
                    ->where('method', 'strip')
                    ->sum('obtained_coins');
            } elseif ($isInApp) {
                return $request->name_for_url_shortcut
                    ? CoinLog::where('user_id', $user?->id)
                    ->where('method', $request->name_for_url_shortcut)
                    ->sum('obtained_coins')
                    : CoinLog::where('user_id', $user?->id)
                    ->whereIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])
                    ->sum('obtained_coins');
            }

            return 0; // Default return value
        };

        // Initialize statistics array
        $statistics = [];

        // Calculate statistics for each field
        foreach (['receiver', 'sender', 'gameCoins', 'luckyGiftCoin'] as $name) {
            $uuid = $request->input("$name.uuid", '0');
            $user = $getUserByUuid($uuid);
            $value = 0;

            if ($name === 'receiver') {
                $value = $calculateReceiverValue($user, $request);
            } elseif ($name === 'sender') {
                $value = Charge::where('charger_id', $user?->id)->sum('amount');
            } elseif ($name === 'gameCoins') {
                $coinResult = CoinGameUser::select(
                    \DB::raw("SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as sum_type_1"),
                    \DB::raw("SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as sum_type_0")
                )->where('user_id', $user?->id)->first();
                $value = ($coinResult->sum_type_1 ?? 0) - ($coinResult->sum_type_0 ?? 0);
            } elseif ($name === 'luckyGiftCoin') {
                $giftResult = UserLuckyGift::select(
                    \DB::raw("SUM(CASE WHEN type = 1 THEN value ELSE 0 END) as sum_type_1"),
                    \DB::raw("SUM(CASE WHEN type = 0 THEN value ELSE 0 END) as sum_type_0")
                )->where('user_id', $user?->id)->first();
                $value = ($giftResult->sum_type_1 ?? 0) - ($giftResult->sum_type_0 ?? 0);
            }

            $statistics[$name] = $value;
        }

        return Common::apiResponse(true, 'Success', $statistics);
    }

    protected function resultReport(Request $request)
    {
        $charger_type = $request->query('name') == 'app' ? 'app' : 'dash';
        $perPage = request('per_page') ?? 10;

        $query = Charge::orderByDesc('created_at')
            ->with(['sender', 'receiver']);

        if ($charger_type == 'dash') {
            $query->where('charger_type', 'dash');
        } else {
            $query->where('charger_type', '!=', 'dash');
        }

        // Apply filters
        if ($request->has('receiver_uuid')) {
            $query->whereHas('receiver', function ($q) use ($request) {
                $q->where('uuid', $request->query('receiver_uuid'));
            });
        }

        if ($request->has('sender_uuid') && $charger_type != 'dash') {
            $query->whereHas('sender', function ($q) use ($request) {
                $q->where('uuid', $request->query('sender_uuid'));
            });
        }

        if ($request->has('search')) {
            $query->where('id', request('search'));
        }

        $charges = $query->paginate($perPage);

        return $charges;
    }

    protected function stripeReport(Request $request)
    {
        $query = CoinLog::orderByDesc('created_at')
            ->whereNotIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])
            ->with('user');
            $perPage = request('per_page') ?? 10;

        if ($request->has('charger_uuid')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('uuid', $request->query('charger_uuid'));
            });
        }

        if ($request->has('search')) {
            $query->where('id', request('search'));
        }

        $logs = $query->paginate($perPage);

        return $logs;
    }

    protected function inAppPurchasReport(Request $request)
    {
        $query = CoinLog::orderByDesc('created_at')
            ->whereIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])
            ->with('user');
            $perPage = request('per_page') ?? 10;

        if ($request->has('charger_uuid')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('uuid', $request->query('charger_uuid'));
            });
        }

        if ($request->has('method')) {
            $query->where('method', $request->query('method'));
        }

        if ($request->has('search')) {
            $query->where('id', request('search'));
        }

        $logs = $query->paginate($perPage);

        return $logs;
    }
}

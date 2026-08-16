<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Models\PaymentCoin;
use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventCoinsReportResource;
use App\Http\Resources\Api\V1\ExchangeCoinsReportResource;
use App\Tik\Services\PaymentGatewayService;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Http\Resources\Api\V1\RecevingReportResource;
use App\Http\Resources\Api\V1\RechargeCoinsReportResource;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\ExchangeLog;
use Modules\DailyPrize\Entities\DailyUserGift;
use Modules\Events\Entities\WinnerReward;

class CoinReportController extends Controller
{
    public function __construct(private PaymentGatewayService $paymentGatewayService) {}

    public function index()
    {
        $type = request('type', 'givin');
        $class = request('class', 'user');

        if ($class === 'user') {
            $data = match ($type) {
                'givin'    => $this->givinCoins(),
                'receving' => $this->receivingCoins(),
                'recharge' => $this->rechargeCoins(),
                default    => [],
            };
        } elseif ($class === 'shipping') {
            $data = $this->rechargeShippingCoins();
        } else {
            $data = [];
        }

        return Common::apiResponse(1, '', $data, 200);
    }

    public function givinCoins()
    {
        $user = auth()->user();
        $data = ExchangeLog::where("user_id", $user->id)
        ->when(request("start_date") && request("end_date"), function ($q) {
            $q->whereDate("created_at", ">=", request("start_date"))
              ->whereDate("created_at", "<=", request("end_date"));
        })
        ->orderBy("created_at", "desc")
        ->paginate(10);//->get();
        return ExchangeCoinsReportResource::collection($data);
    }

    public function receivingCoins()
    {
        $user = auth()->user();
        $data = Charge::where("user_id", $user->id)
        ->when(request("start_date") && request("end_date"), function ($q) {
            $q->whereDate("created_at", ">=", request("start_date"))
              ->whereDate("created_at", "<=", request("end_date"));
        })
        ->with(Common::chargerRelationsQuery())
        ->orderBy("created_at", "desc")

        ->paginate(10);

        return RecevingReportResource::collection($data);
    }


    public function rechargeCoins()
    {
        $user = auth()->user();
        $paymentCoins = PaymentCoin::orderBy('type')->pluck('type')->toArray();
        $data = CoinLog::where("user_id", $user->id)
            ->where('status', 1)
            ->where('user_type', User::class)
            ->whereIn('method', $paymentCoins)
            ->when(request("start_date") && request("end_date"), function ($q) {
                $q->whereDate("created_at", ">=", request("start_date"))
                    ->whereDate("created_at", "<=", request("end_date"));
            })
            ->orderBy("created_at", "desc")
            ->paginate(10);//->get();
        return RechargeCoinsReportResource::collection($data);
    }
    public function rechargeShippingCoins()
    {
        $shippingAgency = auth()->user()->shippingAgency;
        if (!$shippingAgency) {
            return  [];
        }
        
        $paymentCoins = PaymentCoin::orderBy('type')->pluck('type')->toArray();
        $data = CoinLog::where("user_id", $shippingAgency->id)
            ->where('status', 1)
            ->where('user_type', ShippingAgency::class) 
            ->whereIn('method', $paymentCoins)
            ->when(request("start_date") && request("end_date"), function ($q) {
                $q->whereDate("created_at", ">=", request("start_date"))
                    ->whereDate("created_at", "<=", request("end_date"));
            })
            ->orderBy("created_at", "desc")
            ->paginate(10);
        return RechargeCoinsReportResource::collection($data);
    }



    public function eventCoins()
    {
        $user = auth()->user();

        $result1 = $this->getCoinsData(
            DailyUserGift::class,
            ['user_id' => $user->id, 'gift_type' => 'coins'],
            'daily_prize'
        );

        $result2 = $this->getCoinsData(
            WinnerReward::class,
            [],
            'events',
            function ($query) use ($user) {
                $query->whereHas('winnerRow', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->whereHas('reward', function ($q) {
                    $q->where("type", "coins");
                });
            }
        );

        $result = array_merge($result1, $result2);
        return Common::apiResponse(1, '', $result, 200);
    }

    private function getCoinsData($model, array $conditions, $type, $additionalQuery = null)
    {
        $query = $model::where($conditions);

        if ($additionalQuery) {
            $additionalQuery($query);
        }

        $query->when(request("start_date") && request("end_date"), function ($q) {
            $q->whereDate("created_at", ">=", request("start_date"))
              ->whereDate("created_at", "<=", request("end_date"));
        });

        return $query->get()->map(function ($item) use ($type) {
            return new EventCoinsReportResource($item, $type);
        })->toArray();
    }


}

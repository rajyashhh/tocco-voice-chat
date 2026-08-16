<?php

namespace App\Http\Controllers\Web;

use App\Helpers\CoinHelper;
use App\Models\User;
use App\Helpers\Common;
use App\Models\CoinLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Classes\PaymentGateways\Stripe;
use Nafezly\Payments\Classes\OpayPayment;
use Modules\Public\Http\Services\UserCounterServices;

class OPayController extends Controller
{
    public function make($data, $user) {

        $verify_route_name = config('nafezly-payments.OPAY_WEBHOOK_URL');
        $response = Http::withHeaders([
            "MerchantId"=> config('nafezly-payments.OPAY_MERCHANT_ID'),
            "authorization"=>"Bearer ".config('nafezly-payments.OPAY_PUBLIC_KEY'),
            "content-type"=>"application/json"
        ])->post(config('nafezly-payments.OPAY_BASE_URL'),[
            "amount" => [
                "currency" => config('nafezly-payments.OPAY_CURRENCY'),
                "total" => $data['amount']
            ],
            "callbackUrl" => $verify_route_name."?reference_id=".$data['trx'],
            "cancelUrl" => $verify_route_name."?reference_id=".$data['trx'],
            "country" => "EG",
            "expireAt" => 780,
            "payMethod" => "BankCard",
            "productList" => [
                [
                    "description"=>"credit",
                    "name" => "credit",
                    "price" => $data['amount'],
                    "productId" => rand(),
                    "quantity" => 1
                ]
            ],
            "reference" => $data['trx'],
            "returnUrl" => $verify_route_name."?reference_id=".$data['trx'],
            "userInfo" => [
                "userEmail" => $user->email,
                "userId" => $user->id,
                "userMobile" => $user->phone,
                "userName" => $user->name
            ]
        ]);
        $json = $response->json();
        if ($json['code'] == '00000') {
            return Common::apiResponse (1,'here is payment link:',['link' => $json['data']['cashierUrl']]);
        } else {
            logger($json['message']);
            return Common::apiResponse (0,'something wrong happened, try again later',null,400);
        }
    }

    public function verify(Request $request): \Illuminate\Http\JsonResponse
    {

        $payment = new OpayPayment();
        $result = $payment->verify($request);
        if ($result['status']) {
            $coinLog = CoinLog::query ()->where ('trx',$result['data']['reference'])->where ('method','opay')->first ();
            if (!$coinLog) return Common::apiResponse (0,'cannot find transaction',null,404);

            // Claim + credit happen atomically inside applyCoinLog (lock + status
            // re-read), so a replayed callback cannot double-credit.
            CoinHelper::applyCoinLog($coinLog);

            return Common::apiResponse (1,'successfully paid',null,200);
        } else {
            return Common::apiResponse (0,'fail',null,400);
        }
    }
}

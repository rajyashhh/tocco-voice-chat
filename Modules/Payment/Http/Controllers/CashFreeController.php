<?php

namespace Modules\Payment\Http\Controllers;

use App\Helpers\Common;
use App\Models\Coin;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Entities\UserCoinPayment;
use Modules\Payment\Enums\PaymentStatus;


class CashFreeController extends Controller
{


    public function webhook(Request $request)
    {
        try {
            $order = @$request['data']['order'];
            $payment = @$request['data']['payment'];
            if ($order && $payment) {

                $orderId = $order['order_id'];
                $userCoinPayment = UserCoinPayment::query()->where('reference_id', $orderId)->orderByDesc('id')->first();

                if ($userCoinPayment && (strtolower($payment['payment_status']) == PaymentStatus::SUCCESS) && $userCoinPayment->status != PaymentStatus::SUCCESS) {
                    // Claim + credit atomically: lock the payment row and re-read
                    // its status under the lock so a replayed webhook cannot pass
                    // the "not success yet" check twice and double-credit di.
                    DB::transaction(function () use ($userCoinPayment, $payment) {
                        $locked = UserCoinPayment::query()
                            ->whereKey($userCoinPayment->getKey())
                            ->lockForUpdate()
                            ->first();

                        if (!$locked || $locked->status == PaymentStatus::SUCCESS) {
                            return;
                        }

                        $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
                        $coin = $locked->coin;

                        if ($user) {
                            $user->increment('di', $coin->coin ?? 0);
                        }

                        $locked->status = PaymentStatus::SUCCESS;
                        $locked->order_no = $payment['cf_payment_id'];
                        $locked->save();
                    });
                }
            }
        } catch (Exception $e) {
            Log::error('cashfree webhook failed', ['error' => $e->getMessage()]);
        }
        return response()->json();
    }

    public function store(Request $request)
    {
        // Gating consistency (mirrors CoinService::validateGateway): refuse to
        // start a payment when the gateway is toggled off or its credentials are
        // incomplete, so a disabled/misconfigured gateway cannot open an order.
        if (!config('is_cash_free_active')) {
            return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
        }
        foreach (['app_id', 'secret_key', 'mode'] as $requiredKey) {
            if (empty(config("payment.cashfree.{$requiredKey}"))) {
                return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
            }
        }

        $user = Auth::user();
        $idPackage = $request->coins_id;

        $dollarToINR = Common::getConf('dollar_to_INR') ?? 35;
        $coin = Coin::query()->find($idPackage);

        if (!$coin || !$user) die();

        $mode = config('payment.cashfree.mode');
        $url = (($mode == 'test') ? "https://sandbox.cashfree.com" : 'https://api.cashfree.com') . "/pg/orders";

        $headers = ["Content-Type: application/json", "x-api-version: 2023-08-01", //2022-01-01, 2023-08-01
            "x-client-id: " . config('payment.cashfree.app_id'), "x-client-secret: " . config('payment.cashfree.secret_key')];


        $orderId = 'order_' . rand(1111111111, 9999999999);
        $data = json_encode(['order_id' => $orderId, 'order_amount' => $coin->usd * $dollarToINR, "order_currency" => "INR", "customer_details" => ["customer_id" => 'customer_' . rand(111111111, 999999999), "customer_name" => $user->uuid ?? '', "customer_email" => @$user->email ?? '', "customer_phone" => @$user->phone ?? "9999999999",], "order_meta" => ["return_url" => route('cashfree.status', ['orderId' => $orderId])]]);

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $resp = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($resp);

        UserCoinPayment::query()->create(['reference_id' => $orderId, 'user_id' => $user->id, 'coin_id' => $coin->id,]);

        return Common::apiResponse(true, 'Success', $data);
//        return response()->json($data/*->payment_link*/);
    }

    public function orderStatus(Request $request)
    {
        $orderId = $request->ordre_id;
        return view('payment::cashfree.success');
    }
}

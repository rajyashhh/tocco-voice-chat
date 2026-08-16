<?php

namespace App\Http\Controllers\Api\V1;

use Http;
use Throwable;
use App\Helpers\Common;
use App\Models\CoinLog;
use App\Models\GameWallet;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\GameChargeHistory;
use App\Traits\User\PaymentTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethodHistory;


class PaymentMethodController extends Controller
{
    use PaymentTrait;

    public function callback(Request $request)
    {
        $callbackData = $request->all();
        $fawryRefNumber = $callbackData['fawryRefNumber'];
        $merchantRefNumber = $callbackData['merchantRefNumber'];
        $orderStatus = $callbackData['orderStatus'];

        $order = PaymentMethodHistory::where('utd_code', $merchantRefNumber)->first();
        if ($orderStatus === 'PAID') {
            $order->status = 'paid';
            if ($order->type === 'game_type') {
                $this->updateDiForUser($order->amount);
                GameChargeHistory::create([
                    'value' => $order->amount,
                    'admin_id' => 0,
                ]);
            }
        } elseif ($orderStatus === 'CANCELLED') {
            $order->status = 'cancelled';
        } else {
            $order->status = 'Error';
        }
        $order->ref_code = $fawryRefNumber;

        $order->save();

        return true;
    }

    public function updateDiForUser($amount)
    {
        $balance = $amount * config('app.one_coins') * 2;
        $gameWallet = GameWallet::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->first();
        if ($gameWallet) {
            $gameWallet->balance += $balance;
            $gameWallet->save();
        } else {
            GameWallet::create([
                'balance' => $balance,
            ]);
        }
    }

    public function store(Request $request)
    {
        $trx = PaymentMethodHistory::create([
            'amount' => $request->amount,
            'type' => 'game_type',
            'utd_code' => $request->utd_code,
        ]);

        $trxId = $trx->id;

        return Common::apiResponse(1, 'created successfully', $trxId, 200);
    }

    public function utdCallback(Request $request)
    {
        $callbackData = $request->all();
        info('webhook return data', [$callbackData]);
        $fawryRefNumber = $callbackData['fawryRefNumber'];
        $merchantRefNumber = $callbackData['merchantRefNumber'];
        $orderStatus = $callbackData['orderStatus'];

        $paymentMethod = PaymentMethodHistory::where('utd_code', $merchantRefNumber)->first();
        $order = CoinLog::where('trx', $merchantRefNumber)->first();
        if ($orderStatus === 'PAID') {
            $this->webhookPayment($order->id);
            $order->pid = $fawryRefNumber;
            $paymentMethod->status = 'paid';
            $order->save();

            return response()->json(['status' => 'success', 'message' => 'Payment successful.']);
        }
        if ($orderStatus === 'UNPAID') {
            return response()->json(['status' => 'pending', 'message' => 'Payment is still unpaid.'], 202);
        }
        if ($orderStatus === 'CANCELLED') {
            $paymentMethod->status = 'cancelled';

            return response()->json(['status' => 'cancelled', 'message' => 'Payment was cancelled.']);
        }

        $paymentMethod->status = 'Error';
        $paymentMethod->save();
        $order->save();

        return response()->json(['status' => 'error', 'message' => 'Payment status is invalid or failed.'], 400);
    }

    /**
     * Handle PayMob callback forwarded from UTD
     */
    public function utdPayMobCallback(Request $request): JsonResponse
    {
        $webhookData = $request->all();

        $paymob = $webhookData['paymob'] ?? [];
        $obj = $webhookData['obj'] ?? [];
        $order = $obj['order'] ?? [];

        $success = $paymob['success'] ?? $obj['success'] ?? false;
        $transactionId = $paymob['transaction_id'] ?? $obj['id'] ?? $webhookData['paymentRefrenceNumber'] ?? null;
        $amountCents = $paymob['amount_cents'] ?? $obj['amount_cents'] ?? ($webhookData['paymentAmount'] * 100) ?? 0;

        $merchantOrderId = $webhookData['trx_code'] ?? null;
        
        if (!$merchantOrderId && !empty($order['items'])) {
            info('merchantOrderId is null, checking items', ['items' => $order['items']]);
            $itemName = $order['items'][0]['name'] ?? '';
            info('Item name extracted', ['itemName' => $itemName]);
            
            if (preg_match('/Charge Coin - (\d+)/', $itemName, $matches)) {
                $merchantOrderId = $matches[1];
                info('Regex match successful', ['matches' => $matches, 'extracted_merchantOrderId' => $merchantOrderId]);
            } else {
                info('Regex match failed - no match found in item name');
            }
        } else {
            info('merchantOrderId status', [
                'merchantOrderId_exists' => !empty($merchantOrderId),
                'items_empty' => empty($order['items'] ?? [])
            ]);
        }

        if (!$merchantOrderId) {
            return response()->json(['status' => 'error', 'message' => 'Missing merchant order ID'], 400);
        }

        $coinLog = CoinLog::where('trx', $merchantOrderId)->first();
        $paymentMethod = PaymentMethodHistory::where('utd_code', $merchantOrderId)->first();
        if (!$coinLog && !$paymentMethod) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($success) {
            if ($coinLog) {
                $this->webhookPayment($coinLog->id);
                $coinLog->pid = $transactionId;
                $coinLog->save();
            }

            if ($paymentMethod) {
                $paymentMethod->status = 'paid';
                $paymentMethod->ref_code = $transactionId;
                $paymentMethod->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful',
                'transaction_id' => $transactionId,
                'amount' => $amountCents / 100,
            ]);
        }

        if ($paymentMethod) {
            $paymentMethod->status = 'failed';
            $paymentMethod->save();
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Payment failed',
            'transaction_id' => $transactionId,
        ], 400);
    }

    public function success(Request $request): JsonResponse
    {
        try {
            $query = Arr::only($request->query(), [
                'statusCode',
                'statusDescription',
                'merchantRefNumber',
                'orderStatus'
            ]);
                
            // Validate required parameters
            if (empty($query['merchantRefNumber']) || empty($query['statusCode'])) {
                return response()->json([
                    'status' => false,
                    'pending' => false,
                    'trx' => null,
                    'message' => 'Missing required parameters: merchantRefNumber or statusCode.',
                ]);
            }

            $purchaseProduct = CoinLog::where('trx', $query['merchantRefNumber'])->first();

            if (! $purchaseProduct) {
                return response()->json([
                    'status' => false,
                    'pending' => false,
                    'trx' => $query['merchantRefNumber'],
                    'message' => 'Transaction not found.',
                ]);
            }

            if ($query['statusCode'] == 200 && $query['orderStatus'] == 'UNPAID'){
//                $merchantCode = config('services.fawry.merchant_code');
//                $secureKey = config('services.fawry.secure_key');
//                $merchantRefNumber = $query['merchantRefNumber'];
//
//                $signature = hash('sha256', $merchantCode . $merchantRefNumber . $secureKey);

//                $response = Http::get('https://atfawry.com/ECommerceWeb/Fawry/payments/status/v2', [
//                    'merchantCode' => $merchantCode,
//                    'merchantRefNumber' => $merchantRefNumber,
//                    'signature' => $signature,
//                ]);
//
//                $data = $response->json();
//
//                info($data);
//
//                if (!empty($data['paymentStatus'])) {
//                    info('ECommerceWeb', [$data]);
//                }

                return response()->json([
                    'status' => true,
                    'pending' => true,
                    'trx' => $query['merchantRefNumber'],
                    'message' => 'pending',
                ]);
            }

            return response()->json([
                'status' => $query['statusCode'] == 200,
                'pending' => false,
                'trx' => $purchaseProduct->trx,
                'message' => $query['statusDescription'] ?? 'No description provided.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'pending' => false,
                'trx' => null,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

}

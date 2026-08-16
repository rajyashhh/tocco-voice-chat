<?php

namespace App\Http\Controllers;

use App\Enums\Payments\PaymentStatus;
use App\Models\CoinLog;
use App\Services\PayPalService;
use App\Traits\User\PaymentTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    use PaymentTrait;

    public function checkout($logId)
    {
        $log = CoinLog::findOrFail($logId);

        return view('payments.paypal.checkout', [
            'logId'  => $log->id,
            'amount' => $log->paid_usd,
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $paypal = new PayPalService();
        [$orderId, $paymentLink] = $paypal->create($request->referenceId, $request->amount, null);

        $coinLog = CoinLog::whereId($request->referenceId)->first();
        $coinLog->update(['trx' => $orderId]);

        return response()->json([
            'id' => $orderId ?? null,
            'approval_url' => $paymentLink,
        ]);
    }

    public function capture($orderId): JsonResponse
    {
        $orderDetails = (new PayPalService())->capture($orderId);

        return response()->json([$orderDetails]);
    }

    public function transaction($orderId): JsonResponse
    {
        $transactions = (new PayPalService())->transaction($orderId);

        return response()->json([$transactions]);
    }

    public function success($orderId): mixed
    {
        $coinLog = CoinLog::whereId($orderId)->whereMethod('paypal')->firstOrFail();

        $paypal = new PayPalService();
        $response = Http::withToken($paypal->getAccessToken())
            ->get(config('paypal.base_url')."/v2/checkout/orders/$coinLog->trx");

        if ($response->successful()) {
            $data = $response->json();

            $status = $data['status'] ?? null;
            if ($status === 'COMPLETED') {
                return response()->json([
                    'status'  => true,
                    'trx'     => $coinLog->trx,
                    'message' => 'Transaction completed successfully.',
                ]);
            } elseif ($status === 'APPROVED') {
                $response = Http::withToken($paypal->getAccessToken())
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post(config('paypal.base_url') . "/v2/checkout/orders/{$coinLog->trx}/capture", (object)[]);

                if ($response->successful()) {
                    $data = $response->json();

                    $captureStatus = $data['purchase_units'][0]['payments']['captures'][0]['status'] ?? null;

                    if ($captureStatus === 'COMPLETED') {
                        return response()->json([
                            'status'  => true,
                            'trx'     => $coinLog->trx,
                            'message' => 'Transaction completed successfully.',
                        ]);
                    }
                }

                return response()->json([
                    'status'  => true,
                    'trx'     => $coinLog->trx,
                    'message' => 'Transaction approved, pending capture.',
                ]);
            } elseif ($status === 'PENDING') {
                return response()->json([
                    'status'  => true,
                    'trx'     => $coinLog->trx,
                    'message' => 'Transaction pending. Awaiting PayPal review.',
                ]);
            } elseif (in_array($status, ['DENIED', 'FAILED', 'VOIDED', 'CANCELLED'])) {
                return response()->json([
                    'status'  => false,
                    'trx'     => $coinLog->trx,
                    'message' => "Transaction {$status}.",
                ]);
            } else {
                return response()->json([
                    'status'  => false,
                    'trx'     => $coinLog->trx,
                    'message' => 'Transaction failed.',
                ]);
            }
        } else {
            return response()->json([
                'status'  => false,
                'trx'     => $coinLog->trx,
                'message' => 'Failed to retrieve transaction from PayPal.',
            ], 500);
        }
    }

    public function cancel($orderId): JsonResponse
    {
        $coinLog = CoinLog::whereId($orderId)->whereMethod('paypal')->firstOrFail();

        return response()->json([
            'status'  => false,
            'trx'     => $coinLog->trx,
            'message' => 'Transaction cancelled.',
        ], 500);
    }

    public function callback(Request $request): JsonResponse
    {
        $eventType = $request->get('event_type');
        $resource = $request->get('resource');
        $coinLogId = $resource['purchase_units'][0]['reference_id'] ?? null;
        $paypalId   = $resource['id'] ?? null;
        $trx = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if ($trx){
            $coinLog = CoinLog::where('trx', $trx)->first();
        } else {
            $coinLog = CoinLog::where('trx', $paypalId)->first();
        }

        if (! $coinLog){
            info('Failed');
            return response()->json([
                'status'  => 'ignored',
                'trx'     =>  $paypalId,
                'message' => "Failed",
            ]);
        }

        Log::channel('payPal')->info($eventType, $request->all());

        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
                return response()->json([
                    'status'  => true,
                    'trx'     =>  $paypalId,
                    'message' => 'Transaction approved, pending capture.',
                ]);

            case 'PAYMENT.CAPTURE.PENDING':
                $coinLog->update(['status' => PaymentStatus::PENDING, 'trx' => $trx]);
                return response()->json([
                    'status'  => true,
                    'trx'     => $trx,
                    'message' => 'Transaction pending',
                ]);

            case 'PAYMENT.CAPTURE.COMPLETED':
                return $this->webhookPayment($trx, method: 'paypal');

            case 'PAYMENT.CAPTURE.DENIED':
                $coinLog->update(['status' => PaymentStatus::CANCELED, 'trx' => $trx]);
                return response()->json([
                    'status'  => false,
                    'trx'     =>  $trx,
                    'message' => 'Transaction denied.',
                ]);

            case 'PAYMENT.CAPTURE.DECLINED':
                $coinLog->update(['status' => PaymentStatus::CANCELED, 'trx' => $trx]);
                return response()->json([
                    'status'  => false,
                    'trx'     => $trx,
                    'message' => 'Transaction declined.',
                ]);

            default:
                $coinLog->update(['trx' => $paypalId]);
                return response()->json([
                    'status'  => 'ignored',
                    'trx'     => $coinLog?->trx ?? $paypalId,
                    'message' => "Event type {$eventType} not processed.",
                ]);
        }
    }
}

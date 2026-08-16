<?php

namespace App\Http\Controllers;

use App\Models\CoinLog;
use App\Traits\User\PaymentTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtdController extends Controller
{
    use PaymentTrait;

    public function callback(Request $request)
    {
        $utdLog = Log::channel('utd');
        $utdLog->info('callback received', ['payload' => $request->all()]);

        $payload = $request->all();

        $orderId = $payload['reference'] ?? $payload['orderId'] ?? null;
        $event = $payload['event'] ?? null;
        $status = $payload['status'] ?? $payload['resultCode'] ?? $payload['TransactionStatus'] ?? null;
        $gateway = $payload['gateway'] ?? $payload['gatewayName'] ?? null;
        $amount = $payload['amount'] ?? $payload['amountEGP'] ?? null;
        $currency = $payload['currency'] ?? $payload['currencyCode'] ?? null;
        $reference = $payload['reference'] ?? null;

        if (!$orderId) {
            $utdLog->warning('callback missing orderId', $payload);
            return response()->json(['success' => false, 'message' => 'Missing  Parameters'], 200);
        }

        if ($status !== 'success') {
            $utdLog->info('callback payment not successful', ['orderId' => $orderId, 'status' => $status]);
            return response()->json(['success' => false, 'message' => 'Payment not successful', 'status' => $status], 200);
        }

        try {
            $utdLog->info('callback processing payment', ['orderId' => $orderId]);
            return $this->webhookPayment($orderId);
        } catch (\Exception $ex) {
            $utdLog->error('callback error', ['orderId' => $orderId, 'error' => $ex->getMessage()]);
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 500);
        }
    }

    public function success($orderId, Request $request): JsonResponse
    {
        $paymentStatus = $request->query('status', 'success');

        $coinLog = CoinLog::where('id', $orderId)->whereMethod('utd')->first();

        if (!$coinLog) {
            return response()->json([
                'status'  => false,
                'trx'     => $orderId,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return match ($paymentStatus) {
            'success' => response()->json([
                'status'  => true,
                'trx'     => $coinLog->trx,
                'message' => 'Transaction completed successfully.',
            ]),
            'failed' => response()->json([
                'status'  => false,
                'trx'     => $coinLog->trx,
                'message' => 'Transaction failed.',
            ]),
            'cancelled' => response()->json([
                'status'  => false,
                'trx'     => $coinLog->trx,
                'message' => 'Transaction cancelled by user.',
            ]),
            default => response()->json([
                'status'  => false,
                'trx'     => $coinLog->trx,
                'message' => 'Unknown payment status.',
            ]),
        };
    }
}

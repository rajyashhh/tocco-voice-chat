<?php

namespace App\Http\Controllers;

use App\Enums\Payments\PaymentStatus;
use App\Models\CoinLog;
use App\Traits\User\PaymentTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CodapayController extends Controller
{
    use PaymentTrait;

    public function callback(Request $request)
    {
        $txnId = $request->input('TxnId');
        $orderId = $request->input('OrderId');
        $totalPrice = $request->input('TotalPrice');
        $resultCode = $request->input('ResultCode');
        $checksum = $request->input('Checksum');

        $secretKey = config('codapay.api_key');
        $computedChecksum = md5($txnId . $secretKey . $orderId . $resultCode);

        if ($checksum !== $computedChecksum) {
            return response()->json(['error' => 'Invalid checksum'], 403);
        }

        $coinLog = CoinLog::where('id', $orderId)->first();

        if (! $coinLog){
            return response()->json([
                'status'  => 'ignored',
                'trx'     =>  $txnId,
                'message' => "Failed",
            ]);
        }

        if ($resultCode === "0") {
            return $this->webhookPayment($orderId, method: 'codapay', newTrx: $txnId);
        } else {
            $coinLog->update(['status' => PaymentStatus::CANCELED, 'trx' => $txnId]);
            return response()->json(['status'  => false, 'trx' => $txnId, 'message' => 'Transaction declined.',]);
        }
    }

    public function success($id, $country): JsonResponse
    {
        $coinLog = CoinLog::where('id', $id)->whereMethod('codapay')->firstOrFail();

        if (!$id) {
            return response()->json(['status' => 'error', 'message' => 'Missing transaction ID'], 400);
        }

        $baseUrl = config('codapay.base_url');
        $url = $baseUrl . '/api/restful/v2.0/Payment/inquiryPaymentResult.json';
        $body = [
            'inquiryPaymentRequest' => [
                'txnId'          => $coinLog->trx,
                'country'        => $country,
                'apiKey'         => config('codapay.api_key'),
                'projectId'      => config('codapay.project_id'),
                'needStatusFinal'=> true,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $body);

        $json = $response->json();

        $paymentResult = $json['paymentResult'] ?? null;
        $entries = $paymentResult['profile']['entry'] ?? [];

        $statusValue = null;

        foreach ($entries as $entry) {
            if ($entry['key'] === 'status') {
                $statusValue = strtolower($entry['value']);
            }
        }

        switch ($statusValue) {
            case 'success':
                $status = true;
                $message = 'Payment completed successfully!';
                break;
            case 'pending':
                $status = true;
                $message = 'pending';
                break;
            default:
                $status = false;
                $message = 'Payment failed or was cancelled.';
                break;
        }

        return response()->json([
            'status'  => $status,
            'trx'     => $coinLog->trx,
            'message' => $message,
        ]);
    }
}

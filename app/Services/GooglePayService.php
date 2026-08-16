<?php

namespace App\Services;

use App\Helpers\Common;
use App\Traits\User\PaymentTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GooglePayService
{
    protected $baseUrl;
    protected $serverKey;
    use PaymentTrait;

    public function __construct()
    {
        $this->baseUrl = config('googlePay.payment_url');
        $this->serverKey = config('googlePay.node_server_name');
    }

    public function initiatePayment($productId, $trx, $purchaseToken)
    {
        $client = new Client();
        $url = $this->baseUrl . '/api/google-pay';

        try {;
            $response  = $client->post($url, [
                'json' => [
                    'purchaseToken' => $purchaseToken,
                    'productId'     => $productId,
                    'serverKey'     => $this->serverKey
                ],
            ]);
            $body = $response->getBody()->getContents();

            $data = json_decode($body);

            $bodyData = @$data->data ?? null;
            if (@$data->valid && $bodyData) {
                $orderId = $bodyData->orderId;

                $userId = Auth::id();

                $data = $this->makePayment($orderId, $productId, $userId, type: "google_pay");

                if ($data === false) {
                    return response()->json(['status'  => false, 'trx' => $trx, 'message' => __('This operation has already been done before!')]);
                } else {
                    return response()->json(['status'  => true, 'trx' => $trx, 'message' => __('Added successfully')]);
                }
            }
        } catch (GuzzleException $e) {
            return response()->json(['status'  => false, 'trx' => $trx, 'message' => __('There was a problem, please try again!')]);
        }
        return response()->json(['status'  => false, 'trx' => $trx, 'message' => __('There was a problem, please try again!')]);
    }
}

<?php

namespace App\Services;

use App\Models\CoinLog;
use App\Models\PaymentMethodHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FawryPaymentService
{
    protected $fawryUrl;

    public function __construct()
    {

        $this->fawryUrl = config('services.utd_fawry.utd_fawry_url');
    }

    public static function redirect_if_payment_success($trx)
    {
       return url(config("services.utd_fawry.utd_fawry_return_url"));
    }

   public static function redirect_if_payment_faild($trx)
   {
    return url("/admin/payment-with-method");
   }

    public function makePayment($trx,$amount,$exterData)
    {
        PaymentMethodHistory::create([
            "amount" => $amount,
            "type" => 'game_type',
            "utd_code" => $trx
        ]);
        $data = $this->getBodyForFawry($trx,$amount);
        $data['paymentSubType'] = $exterData['type'];
        $data['paymentType'] = $exterData['paymentType'];
        $utdUrl = config("services.utd_fawry.utd_url");
        $response = Http::post($utdUrl, $data);
        info($response);
        return json_decode($response);
    }

        public function makePaymentLink($trx,$amount,$exterData)
    {
        $data = $this->getBodyForFawry($trx,$amount);
        $data['paymentSubType'] = $exterData['type'];
        $data['paymentType'] = $exterData['paymentType'];
        $utdUrl = config("services.utd_fawry.utd_url");
        $response = Http::post($utdUrl, $data);
        info($response);
        return json_decode($response);
    }
    public function utdFawryInitial($trx,$amount,$exterData)
    {
       $data =  $this->getBodyForFawry($trx,$amount);
       $data['paymentSubType'] = $exterData['type'];
       $data['paymentType'] = $exterData['paymentType'];
        $body = [
            "data" => $data,
            "code" => $trx
        ];

        $url = config("services.utd_fawry.utd_url");

        $client = new Client();

//        try {
            $response = Http::withOptions(['verify' => false])->post($url, [
                'json' => $body,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);

        return json_decode($response->getBody(), true);

//        } catch (RequestException $e) {
//            if ($e->hasResponse()) {
//                $errorResponse = json_decode($e->getResponse()->getBody(), true);
//                return $errorResponse; // التعامل مع الخطأ
//            }
//
//            return ['error' => 'Error occurred while making the API call'];
//        }
    }

    public function getBodyForFawry($trx,$amount)
    {
        $merchantCode = config("services.utd_fawry.utd_fawry_merchant_code");
        $merchantRefNum = $trx;
        $secure_key = config("services.utd_fawry.utd_fawry_secret");
        $price = number_format($amount, 2, '.', '');
        $qty = 1;
        $syn = $merchantCode.$merchantRefNum."".self::redirect_if_payment_success ($trx).$trx.$qty.$price.$secure_key;
        $signature = hash('sha256', $syn);
        $data = [
            "merchantCode"=> $merchantCode,
            "merchantRefNum"=> $merchantRefNum,
            "language" => "en-gb",
            "chargeItems"=> [
                [
                    "itemId"=> $trx,
                    "price"=> $price,
                    "quantity"=> $qty,
                ]
            ],
            "returnUrl"=> self::redirect_if_payment_success($trx),
            "signature"=> $signature

        ];
        return $data;
    }


}

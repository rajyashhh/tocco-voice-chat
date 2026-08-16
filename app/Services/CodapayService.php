<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class CodapayService
{
    protected $baseUrl;
    protected $apiKey;
    protected $projectId;
    protected $country;
    protected $payType;
    protected $currency;
    public function __construct()
    {
        $this->baseUrl = config('codapay.base_url');
        $this->apiKey = config('codapay.api_key');
        $this->projectId = config('codapay.project_id');
        if (auth()->check() && auth()->user()->country) {
            $this->country = auth()->user()->country->iso_numeric;
        } else {
            $countryId = Setting::where('key', 'default_country')->first()->value;
            $country = Country::whereId($countryId)->select(['id', 'iso_numeric'])->first();
            $this->country = $country->iso_numeric;
        }

//        $this->baseUrl = 'https://airtime.codapayments.com/airtime';
//        $this->apiKey = 'live_JI4WS6k27hHslcUOcmC9SGFDiyo';
//        $this->projectId = 289;
//        $this->country = 818;
    }

    public static function redirect_if_payment_success($trx, $country)
    {
        return url("/api/codapay-success/$trx/$country");
    }

    public function initiatePayment($trx, $amount, $userId = null)
    {
        $body = $this->getBodyForCodapay($trx, $amount, $userId);
        $url = $this->baseUrl.'/api/restful/v2.0/Payment/init.json';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $body);


        $json = $response->json();
        $txnId = $json['initResult']['txnId'];
        if ($txnId){
            return $this->baseUrl."/begin?type=3&txn_id=$txnId";
        }
    }

    protected function getBodyForCodapay($trx, $amount, $userId): array
    {
        return [
            'initRequest' => [
                'country'   => $this->country,
                'payType'   => 0,
                'apiKey'    => $this->apiKey,
                'projectId' => $this->projectId,
                'orderId'   => (string)$trx,
                'currency'  => 840,
                'items' => [
                    [
                        'code'  => '1',
                        'price' => floatval($amount),
                        'name'  => 'Order #' . $trx,
                    ]
                ],
                'profile' => [
                    'entry' => [
                        [
                            'key'   => 'user_id',
                            'value' => (string)($userId ?? 'guest'),
                        ],
                        [
                            "key" => "return_url",
                            "value" => self::redirect_if_payment_success($trx, $this->country)
//                            "value" => "https://www.example.com/{transactionId}/{orderId}/return"
                        ]
                    ]
                ]
            ]
        ];
    }

}

<?php

namespace App\Services;

use App\Models\PaymentMethodHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class FawryPaymentServiceV2
{
    protected $fawryUrl;

    public function __construct()
    {

        $this->fawryUrl = config('services.fawry.fawry_url');
    }

    public static function redirect_if_payment_success($trx)
    {
       return url(config("services.fawry.fawry_return_url"));
    }

   public static function redirect_if_payment_faild($trx)
   {
    return url("/admin/payment-with-method");
   }

    public function makePayment($trx,$amount,$exterData): string
    {
        $data = $this->getBodyForFawry($trx,$amount);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->fawryUrl, $data);

        return $response->body();
    }

    public function getBodyForFawry($trx,$amount): array
    {
        $merchantCode = config("services.fawry.fawry_merchant_code");
        $merchantRefNum = $trx;
        $secure_key = config("services.fawry.fawry_secret");
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

<?php
namespace App\Classes\PaymentGateways;
use App\Helpers\Common;
use App\Models\CoinLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Fawry
{

    protected $url = 'https://atfawry.com/fawrypay-api/api/payments/init';
    protected $test_url = 'https://atfawry.fawrystaging.com/fawrypay-api/api/payments/init';


	 public static function redirect_if_payment_success($trx)
     {
        return url("/payment/payment-success?p_method=fawry&&trx=$trx");
     }

    public static function redirect_if_payment_faild($trx)
    {
     return url("/payment/payment-fail?p_method=fawry&&trx=$trx");
    }


    public function make($data){


        $c = CoinLog::query ()->where ('trx',$data['trx'])->where ('method','fawry')->where ('status',0)->first ();
        if($c){
            $c->pid = '$checkout_session->id';
            $c->save ();
        }

        $merchantCode = "1tSa6uxz2nRbgY+b+cZGyA==";//from conf // from fawry account
        $merchantRefNum = "2312465464";//from conf // from fawry account
        $secure_key = "68a1fafd-e49a-4aad-a6f4-203c94510091";//from conf // from fawry account
        $price = number_format($data['amount'], 2, '.', '');
        $qty = 1;
        $syn = $merchantCode.$merchantRefNum."".self::redirect_if_payment_success ($data['trx']).$data['trx'].$qty.$price.$secure_key;
        $signature = hash('sha256', $syn);
        $data = [
            "merchantCode"=> $merchantCode,
            "merchantRefNum"=> $merchantRefNum,
            "language" => "en-gb",
            "chargeItems"=> [
                [
                    "itemId"=> $data['trx'],
                    "price"=> $price,
                    "quantity"=> $qty,
                ]
            ],
            "returnUrl"=> self::redirect_if_payment_success ($data['trx']),
            "signature"=> $signature

        ];

        $response = Http::post ($this->test_url,$data)->json ();

        dd ($response);

        return $response;

    }


    public static function status($session_id) {



    }
     public function __construct()
    {

    }

}

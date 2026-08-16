<?php
namespace App\Classes\PaymentGateways;
use App\Helpers\Common;
use App\Models\CoinLog;

class Stripe
{

    //email : test@rxample.com
    //Card information : 4242 4242 4242 4242     12/34    567
    //Name on card : Zhang San
    //Country or region : United States
    //zip : 12345

	 public static function redirect_if_payment_success($data)
     {
        $trx = $data['trx'];
        return url($data['stripe_success_url']?->value.$trx);
     }

    public static function redirect_if_payment_faild($data)
    {
        $trx = $data['trx'];
        return url($data['stripe_cancel_url']?->value .$trx);
    }


    public function make($data){
        \Stripe\Stripe::setApiKey($data['stripe_test_secret_key']);
        $checkout_session = \Stripe\Checkout\Session::create(
            [
                'line_items' => [
                    [
                        'price_data' => [
                            'currency'=> $data['stripe_currency'],
                            'product_data'=>[
                                'name'=>$data['name']
                            ],
                            'unit_amount'=>100 * $data['amount']
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'metadata' => [
                    'order_id' => $data['order_id'] ?? '',
                    'user_id' => $data['user_id'] ?? '',
                ],
                'success_url' => url('/api/payment/success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => url('/api/payment/cancel'),
            ]
        );

        $c = CoinLog::query ()->where ('trx',$data['trx'])->where ('method','strip')->where ('status',0)->first();
        if($c){
            $c->pid = $checkout_session->id;
            $c->save ();
        }
        return $checkout_session->url;

    }


    public static function status($session_id, $secret) {

        $value = $secret->value;
        $stripe = new \Stripe\StripeClient($value);
        try {
            $session = $stripe->checkout->sessions->retrieve($session_id);
            return $session;
        }catch (\Exception $exception){
            return Common::apiResponse (0,'fail',null,400);
        }

    }
     public function __construct()
    {

    }

}

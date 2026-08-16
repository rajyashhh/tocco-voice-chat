<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Nafezly\Payments\Classes\OpayPayment;

class TestController extends Controller
{
    public function index() {

        $verify_route_name = config('nafezly-payments.VERIFY_ROUTE_NAME');
        $unique_id=uniqid();
        $response = Http::withHeaders([
            "MerchantId"=> env('OPAY_MERCHANT_ID'),
            "authorization"=>"Bearer ".env('OPAY_PUBLIC_KEY'),
            "content-type"=>"application/json"
        ])->post('https://sandboxapi.opaycheckout.com/api/v1/international/cashier/create',[
            "amount" => [
                "currency" => env('OPAY_CURRENCY'),
                "total" => 500
            ],
            "callbackUrl" => $verify_route_name."?reference_id=".$unique_id,
            "cancelUrl" => $verify_route_name."?reference_id=".$unique_id,
            "country" => "EG",
            "expireAt" => 780,
            "payMethod" => "BankCard",
            "productList" => [
                [
                    "description"=>"credit",
                    "name" => "credit",
                    "price" => 500,
                    "productId" => rand(),
                    "quantity" => 1
                ]
            ],
            "reference" => $unique_id,
            "returnUrl" => $verify_route_name."?reference_id=".$unique_id,
            "userInfo" => [
                "userEmail" => 'ahmed@gmail.com',
                "userId" => rand(),
                "userMobile" => '+201110231321',
                "userName" => 'Ahmed Ramadan'
            ]
        ]);
        //dd($response, $response->json());
    }

    public function payment_verify(Request $request) {

        $payment = new OpayPayment();
        $test = $payment->verify($request);
        //dd($test);
        if ($test['status']) {

        } else {

        }
    }
}

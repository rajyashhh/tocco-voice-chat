<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\User;
use App\Tik\Repositories\CoinLogRepository;
use App\Tik\Repositories\CoinRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Traits\Processor;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Cache;

class Paytabs
{
    use Processor;

    private $config_values;

    public function __construct()
    {
        $this->config_values = [
            'profile_id'        => Common::getSettingValue('paytabs_profile_id'),
            'server_key'        => Common::getSettingValue('paytabs_server_key'),
            'base_url'          => Common::getSettingValue('paytabs_base_url'),
            'payment_address'   => Common::getSettingValue('paytabs_payment_address'),

        ];

    }

    public function getConfig($key)
    {
        return $this->config_values[$key] ?? null;
    }

    function send_api_request($request_url, $data, $request_method = null)
    {
        $data['profile_id'] = $this->getConfig('profile_id');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getConfig('base_url') . '/' . $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CUSTOMREQUEST => isset($request_method) ? $request_method : 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, true),
            CURLOPT_HTTPHEADER => array(
                'authorization:' . $this->getConfig('server_key'),
                'Content-Type:application/json'
            ),
        ));

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }

    function is_valid_redirect($post_values)
    {


        $serverKey = $this->getConfig('server_key');

        $rawPayload = file_get_contents('php://input');

        $requestSignature = request()->header('signature');

        $calculatedSignature = hash_hmac('sha256', $rawPayload, $serverKey);

        return hash_equals($calculatedSignature, $requestSignature);
    }



}

class PaytabsController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private CoinLogRepository $coinLogRepository;
    private CoinRepository $coinRepository;
    private $user;

    public function __construct(

         PaymentRequest $payment,
         User $user,
         CoinLogRepository $coinLogRepository,
         CoinRepository $coinRepository,
         )
    {
        $this->payment = $payment;
        $this->user = $user;
        $this->coinLogRepository = $coinLogRepository;
        $this->coinRepository = $coinRepository;
    }

    public function payment(Request $request)
    {

        $user = $request->user();
        $validator = Validator::make($request->all(), [
                'coin_id' => 'required',
                'pay_method' => 'required',
            ]);
       if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }
        $coin = $this->coinRepository->findById($request->coin_id);
        if (!$coin) return Common::apiResponse(0, 'not found', null, 404);
        $trx = rand(111111111111111111, 999999999999999999);
            $dataCoinLog = [
                'paid_usd' => $coin->usd,
                'obtained_coins' => $coin->coin,
                'user_id' => $user->id,
                'method' => $request->pay_method,
                'trx' => $trx,
                'status' => 0,
                'coin_id' => $request->coin_id,
            ];


        $log = $this->coinLogRepository->create($dataCoinLog);


        $plugin = new Paytabs();
        $request_url = 'payment/request';
        $data = [
            "tran_type" => "sale",
            "tran_class" => "ecom",
            "cart_id" => 'invoice_' . $log->id,
            "cart_currency" => 'EGP',
            "cart_amount" => round($log->paid_usd, 2),
            "cart_description" => "products",
            "paypage_lang" => "en",
            "callback" => route('paytabs.callback'),
            "return" => route('paytabs.return', ['payment_id' => $log->id]),
            "customer_details" => [
                "name" => $user->name,
                "email" => $user->email,
                "phone" => $user->phone ?? "000000",
                // "street1" => "N/A",
                // "city" => "N/A",
                // "state" => "N/A",
                // "country" => "N/A",
                // "zip" => "00000"
            ],
            // "shipping_details" => [
            //     "name" => "N/A",
            //     "email" => "N/A",
            //     "phone" => "N/A",
            //     "street1" => "N/A",
            //     "city" => "N/A",
            //     "state" => "N/A",
            //     "country" => "N/A",
            //     "zip" => "0000"
            // ],
            // "user_defined" => [
            //     "udf9" => "UDF9",
            //     "udf3" => "UDF3"
            // ]
        ];

        $page = $plugin->send_api_request($request_url, $data);
        if (!isset($page['redirect_url'])) {
            return Common::apiResponse(0, 'try leter', null, 404);
        }
        return Common::apiResponse(1, '', ['url' => $page['redirect_url']], 200);

    }

    public function callback(Request $request)
    {
        $response_data = $request->post();

        $transRef = $response_data['tran_ref'] ?? null;
        $cartId = $response_data['cart_id'] ?? null;


        $invoiceNumber = null;
        if ($cartId) {
            $parts = explode('_', $cartId);
            if (isset($parts[1])) {
                $invoiceNumber = $parts[1];
            }
        }

        if (!$transRef) {
            return Common::apiResponse(0, 'try later', null, 200);
        }
        $plugin = new Paytabs();



        $request_url = 'payment/query';
        $data = ["tran_ref" => $transRef];
        $verify_result = $plugin->send_api_request($request_url, $data);
        $is_valid = $plugin->is_valid_redirect($request);

        if (!$is_valid) {
            return Common::apiResponse(0, 'try later', null, 200);
        }


        $is_success = isset($verify_result['payment_result']['response_status']) &&
                    $verify_result['payment_result']['response_status'] === 'A';

        $payment_data = $this->coinLogRepository->getCoinsById($invoiceNumber);

        if ($payment_data) {
        } else {
        }


    if ($is_success) {
        if ($payment_data) {

            $payment_data->update([
                'status' => 1,
                'trx' => $transRef,
            ]);
            if ($payment_data->pid == 1) {

                $this->onPaymentSuccess($payment_data);
            }


        }
        return $this->payment_response($payment_data, 'success');
    } else {
        if ($payment_data && $payment_data->pid == 0) {
            $this->onPaymentFailure($payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
    }



    public function onPaymentSuccess($payment_data)
    {
    }

        public function onPaymentFailure($payment_data)
        {
            \Log::warning("فشل الدفع للطلب رقم: " . $payment_data->id);
        }

    public function return(Request $request)
    {
        return response()->json(['message' => 'Callback received'], 200);
    }
}

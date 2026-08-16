<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PayPalService
{


    public function __construct()
{
    $clientId = config('paypal.client_id');
    $clientSecret = config('paypal.client_secret');
//    $environment = new ProductionEnvironment($clientId, $clientSecret);
//    $environment = new SandboxEnvironment($clientId, $clientSecret);
//    $this->client = new PayPalHttpClient($environment);
}

   public static function redirectUrl()
   {
    return url("/admin/payment-with-method");
   }

    public function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('paypal.client_id'), config('paypal.client_secret'))
            ->post(config('paypal.base_url') . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to retrieve PayPal access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    /**
     * @return string
     */
    public function create(int $referenceId, $amount, $user): array|string
    {
        $id = uuid_create();

        $headers = [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Bearer ' . $this->getAccessToken(),
            'PayPal-Request-Id' => $id,
        ];
        $body = [
            "intent"         => "CAPTURE",
            'application_context' => [
                "payment_method_preference"=> "IMMEDIATE_PAYMENT_REQUIRED",
                'return_url'  => url("/api/paypal-return/$referenceId"),
                'cancel_url'  => url("/api/paypal-cancel/$referenceId"),
                'user_action' => 'PAY_NOW',
//                'shipping_preference' => 'NO_SHIPPING',
//                'landing_page' => 'BILLING',
            ],
            "purchase_units" => [
                [
                    "reference_id" => $referenceId,
                    "amount"       => [
                        "currency_code" => config('paypal.currency'),
                        "value"         => number_format($amount, 2),
                    ],
                    // "shipping" => [
                    //     "address" => [
                    //         "address_line_1" => "Test Street",
                    //         "admin_area_2"   => "London",
                    //         "postal_code"    => "12345",
                    //         "country_code"   => "GB"
                    //     ]
                    //     ],
                ]
            ],
        ];

        $response = Http::withHeaders($headers)
            ->withBody(json_encode($body))
            ->post(config('paypal.base_url'). '/v2/checkout/orders');

        if (isset($response['id']) && $response['status'] == 'CREATED') {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $paymentLink = $link['href'];
                }
            }
        }

        return [$response['id'], $paymentLink];
    }



    public function createOrder($referenceId, $amount, $user)
    {
        $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => (string)$referenceId,
                "amount" => [
                    "currency_code" => config('paypal.currency', 'USD'),
                    "value" => number_format((float)$amount, 2, '.', '')
                ]
            ]],
            "application_context" => [
                "brand_name"            => config('app.name'),
                "landing_page"          => "BILLING",         // يحاول إظهار شاشة البطاقة
                "user_action"           => "PAY_NOW",
                "shipping_preference"   => "NO_SHIPPING",     // اختياري
                "return_url"            => url("/api/paypal-return/$referenceId"),
                "cancel_url"            => url('/api/paypal-cancel'),
            ]
        ];

        $response = $this->client->execute($request);

        return $orderId  = $response->result->id ?? null;
        $status   = $response->result->status ?? null;

        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                // أرجع الرابط كما هو، بدون أي تعديل
                return $link->href;
            }
        }
        throw new \Exception("PayPal approval link not found");
    }


    /**
     * @return mixed
     */
//    public function success(Request $request)
//    {
//        sleep(29);
//        $orderId = $request->query('token');
//        if (! $orderId) {
//            return response()->json([
//                'status'  => 'error',
//                'message' => 'Missing PayPal order id',
//            ], 422);
//        }
//
//        $url = config('paypal.base_url') . "/v2/checkout/orders/{$orderId}/capture";
//        $headers = [
//            'Content-Type'  => 'application/json',
//            'Authorization' => 'Bearer ' . $this->getAccessToken(),
//        ];
//
//        $response = Http::withHeaders($headers)->post($url, null);
//
//        if ($response->failed()) {
//            return response()->json([
//                'status'  => 'error',
//                'message' => data_get($response->json(), 'message', 'Payment capture failed'),
//                'details' => $response->json(),
//            ], $response->status());
//        }
//
//        $data = $response->json();
//
//        if (data_get($data, 'status') !== 'COMPLETED') {
//            return response()->json([
//                'status'  => 'error',
//                'message' => 'Payment not completed',
//                'details' => $data,
//            ], 409);
//        }
//
//        $referenceId = data_get($data, 'purchase_units.0.reference_id');
//        $amount      = (float) data_get($data, 'purchase_units.0.payments.captures.0.amount.value');
//
//        return response()->json([
//            'status'  => 'success',
//            'order'   => $orderId,
//            'amount'  => $amount,
//        ], 200);
//    }

    public function transaction($orderId)
    {
        $response = Http::withToken($this->getAccessToken())
            ->get(config('paypal.base_url')."/v2/checkout/orders/$orderId");

        return $response->json();
    }
}

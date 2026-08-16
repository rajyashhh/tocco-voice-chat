<?php

namespace App\Services;

use App\Models\PaymentMethodHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobPaymentService
{
    protected $paymobUrl;

    public function __construct()
    {
        $this->paymobUrl = config('services.utd_paymob.utd_paymob_url');
    }

    public static function redirect_if_payment_success($trx)
    {
        return url(config("services.utd_paymob.utd_paymob_return_url"));
    }

    public static function redirect_if_payment_faild($trx)
    {
        return url("/admin/payment-with-method");
    }

    public function makePayment($trx, $amount, $exterData)
    {
        $utdUrl = config("services.utd_paymob.utd_url");
        $merchantCode = config("services.utd_paymob.utd_paymob_merchant_code");
        $secret = config("services.utd_paymob.utd_paymob_secret");

        
        $merchantCode = config("services.utd_paymob.utd_paymob_merchant_code");
        $secret = config("services.utd_paymob.utd_paymob_secret");
        $returnUrl = url(config("services.utd_paymob.utd_paymob_return_url"));
        $orderId = 'ORDER-' . $trx . '-' . time();
        $price = number_format($amount, 2, '.', '');

        $syn = $merchantCode . $orderId . "" . $returnUrl . $orderId . "1" . $price . $secret;
        $signature = hash('sha256', $syn);

        $data = [
            'returnUrl' => $returnUrl,
            'merchantCode' => $merchantCode,
            'chargeItems' => [
                [
                    'itemId' => $orderId,
                    'price' => (float) $amount,
                ]
            ],
            'signature' => $signature,
            'special_reference' => $orderId,
            'amount' => (float) $amount,
            'description' => 'Payment via makePayment - ' . $trx,
            'paymentSubType' => $exterData['type'] ?? 'game_type',
            'paymentType' => $exterData['paymentType'] ?? 'revenue',
            'payment_method' => $exterData['payment_method'] ?? 'card',
        ];

        if (isset($exterData['wallet_phone'])) {
            $data['wallet_phone'] = $exterData['wallet_phone'];
        }

        $utdUrl = config("services.utd_paymob.utd_url");
        
        if (empty($utdUrl)) {
            $utdUrl = config("services.utd_paymob.utd_paymob_url");
        }
        
        if (empty($utdUrl)) {
            return [
                'status' => 0,
                'message' => 'Payment gateway configuration error: Missing URL',
                'data' => null
            ];
        }
        
        $baseUrl = preg_replace('/\/api\/.*$/', '/api/paymob-intention', $utdUrl);
        
        if ($baseUrl === $utdUrl && !str_contains($utdUrl, '/api/paymob-intention')) {
            $baseUrl = rtrim($utdUrl, '/') . '/api/paymob-intention';
        }
        
        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        
            return [
                'status' => 0,
                'message' => 'Payment gateway configuration error: Invalid URL',
                'data' => null
            ];
        }
        
    
        
        try {
            $response = Http::timeout(30)->post($baseUrl, $data);
        
        } catch (\Exception $e) {
           
            return [
                'status' => 0,
                'message' => 'Connection error: ' . $e->getMessage(),
                'data' => null
            ];
        }

        $responseData = $response->json();
        
        if (!$response->successful()) {
            return [
                'status' => 0,
                'message' => $responseData['message'] ?? 'Payment request failed',
                'data' => $responseData
            ];
        }
        
        // Check if responseData is directly a URL string
        if (is_string($responseData) && filter_var($responseData, FILTER_VALIDATE_URL)) {
            return [
                'status' => 1,
                'payment_url' => $responseData,
                'message' => 'Payment link created successfully',
                'data' => ['url' => $responseData]
            ];
        }
        
        // Check for payment URL in different possible fields (for object responses)
        $paymentUrl = null;
        if (is_array($responseData) || is_object($responseData)) {
            $responseArray = (array) $responseData;
            if (isset($responseArray['payment_url'])) {
                $paymentUrl = $responseArray['payment_url'];
            } elseif (isset($responseArray['redirectionUrl'])) {
                $paymentUrl = $responseArray['redirectionUrl'];
            } elseif (isset($responseArray['url'])) {
                $paymentUrl = $responseArray['url'];
            } elseif (isset($responseArray['checkout_url'])) {
                $paymentUrl = $responseArray['checkout_url'];
            } elseif (isset($responseArray['payment_link'])) {
                $paymentUrl = $responseArray['payment_link'];
            }
        }
        
        if ($paymentUrl && filter_var($paymentUrl, FILTER_VALIDATE_URL)) {
            return [
                'status' => 1,
                'payment_url' => $paymentUrl,
                'message' => 'Payment link created successfully',
                'data' => $responseData
            ];
        }
        
        return [
            'status' => 1,
            'payment_url' => $responseData,
            'message' => 'Payment processed but no URL found',
            'data' => $responseData
        ];
    }

    public function getBodyForPaymob($trx, $amount)
    {
        $merchantCode = config("services.utd_paymob.utd_paymob_merchant_code");
        $merchantRefNum = $trx;
        $secure_key = config("services.utd_paymob.utd_paymob_secret");
        $price = number_format($amount, 2, '.', '');
        $qty = 1;
        $syn = $merchantCode.$merchantRefNum."".self::redirect_if_payment_success($trx).$trx.$qty.$price.$secure_key;
        $signature = hash('sha256', $syn);

        $data = [
            "merchantCode" => $merchantCode,
            "merchantRefNum" => $merchantRefNum,
            "language" => "en-gb",
            "chargeItems" => [
                [
                    "itemId" => $trx,
                    "price" => $price,
                    "quantity" => $qty,
                ]
            ],
            "returnUrl" => self::redirect_if_payment_success($trx),
            "signature" => $signature,
        ];

        return $data;
    }

    public function createPaymentLink($amount, $name, $description = '', $email = null, $phone = null, $trx = null, $expiresAt = null, $isLive = false )
    {
        PaymentMethodHistory::create([
            "amount" => $amount,
            "payment_method" => 'paymob',
            "type" => 'game_type',
            "utd_code" => $trx
        ]);

        $utdUrl = config("services.utd_paymob.utd_url");
        $baseUrl = preg_replace('/\/api\/.*$/', '/api/paymob-intention', $utdUrl);

        $merchantCode = config("services.utd_paymob.utd_paymob_merchant_code");
        $secure_key = config("services.utd_paymob.utd_paymob_secret");
        $returnUrl = url(config("services.utd_paymob.utd_paymob_return_url"));
        $orderId = 'ORDER-' . time();
        $price = number_format($amount, 2, '.', '');

        $syn = $merchantCode . $orderId . "" . $returnUrl . $orderId . "1" . $price . $secure_key;
        $signature = hash('sha256', $syn);

        // Split name into first_name and last_name
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? 'User';

        $data = [
            'returnUrl' => $returnUrl,
            'merchantCode' => $merchantCode,
            'chargeItems' => [
                [
                    'itemId' => $orderId,
                    'price' => (float) $amount,
                ]
            ],
            'signature' => $signature,
            'special_reference' => $orderId,
            'amount' => (float) $amount,
            'description' => $description,
            'billing_data' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phone,
            ],
        ];

     

        try {
            $response = Http::timeout(30)->post($baseUrl, $data);

        
        } catch (\Exception $e) {
         
            return [
                'status' => 0,
                'message' => 'Connection error: ' . $e->getMessage(),
                'data' => null
            ];
        }

        $responseData = $response->json();
        
        if (!$response->successful()) {
            return [
                'status' => 0,
                'message' => $responseData['message'] ?? 'Failed to create payment link',
                'data' => $responseData
            ];
        }
        
        if (is_string($responseData) && filter_var($responseData, FILTER_VALIDATE_URL)) {
            return $responseData;     
        }
        

    }
}

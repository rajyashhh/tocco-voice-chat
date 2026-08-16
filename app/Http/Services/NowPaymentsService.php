<?php

namespace App\Http\Services;

use Database\Seeders\config;
use GuzzleHttp\Client;
use Log;
use Illuminate\Support\Facades\Http;


class NowPaymentsService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.nowpayments.io/v1/',
            'headers' => [
                'x-api-key' => config('services.now_payments.api_key'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function createInvoice($request)
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.now_payments.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.nowpayments.io/v1/invoice', [
            'price_amount' => $request->amount,
            'price_currency' => 'usd',
            'pay_currency' => $request->currency,
            'order_id' => uniqid(),
            'order_description' => 'Wallet top-up',
            'ipn_callback_url' => config('services.now_payments.callback_url'),
            'success_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
        ]);
    
        if ($response->successful()) {
            return $response->json(); // يحتوي على  وغيره
        }
    
        throw new \Exception('Invoice creation failed: ' . $response->body());
    }
    public function getCurrencies(){
        $response = $this->client->get('currencies', [
            'query' => [
                'fixed_rate' => 'true',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
    public function createPayment(array $data)
    {
        $response = $this->client->post('payment', [
            'json' => $data,
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getPaymentStatus($paymentId)
    {
        $response = $this->client->get("payment/{$paymentId}");

        return json_decode($response->getBody(), true);
    }

    public function paymentSuccess()
    {
        return view('payments.now_payments.success');
    }

    public function paymentCancel()
    {
        return view('payments.now_payments.cancel');
    }
}

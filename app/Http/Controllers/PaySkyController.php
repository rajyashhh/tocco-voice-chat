<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Database\Seeders\config;
use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class PaySkyController extends Controller
{

    protected $client;
    protected $apiUrl;
    protected $merchant_id;
    protected $terminal_id;
    protected $secret_key;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('paysky.base_url');
        $this->merchant_id = config('paysky.merchant_id');
        $this->terminal_id = config('paysky.terminal_id');
        $this->secret_key = config('paysky.api_key');

    }
    public function pay(Request $request){


        $data = [
            'MerchantId' => $this->merchant_id,
            'TerminalId' => $this->terminal_id,
            'DateTimeLocalTrxn' => Carbon::now()->format('YmdHis'),
            'Message' => 'Approved',
            'TxnType' => 1,
            'PaidThrough' => 'Card',
            'Amount' => $request->amount,
            'Currency' => '818', // EGP
            'PayerAccount' => $request->card_number,
            'SystemReference' => '534727'
        ];

        $data['SecureHash'] = $this->generateSecureHash($data, $this->secret_key);

        $response  = $this->client->post($this->apiUrl, [
            'json' => $data
        ]);

        $result = json_decode($response->getBody(), true);

        //dd($result,$this->apiUrl, $data);

    }



    private function generateSecureHash($data, $secretKeyHex)
    {
        $data = [
            'Amount' => $data['Amount'],
            'Currency' => $data['Currency'],
            'DateTimeLocalTrxn' => $data['DateTimeLocalTrxn'],
            'MerchantId' => $data['MerchantId'],
            'TerminalId' => $data['TerminalId'],
        ];

        ksort($data);

        $hashString = collect($data)->map(function ($value, $key) {
            return $key . '=' . $value;
        })->join('&');

        $secretKey = hex2bin($secretKeyHex);


        if (!$secretKey) {
            throw new Exception('Invalid Secret Key Hex Format.');
        }

        // Step 4: Generate SHA-256 HMAC
        $hash = hash_hmac('sha256', $hashString, $secretKey);

        // Step 5: Convert to uppercase hexadecimal
        return strtoupper($hash);
    }

}

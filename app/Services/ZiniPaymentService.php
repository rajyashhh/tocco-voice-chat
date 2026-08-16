<?php

namespace App\Services;

use App\Models\PaymentMethodHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZiniPaymentService
{
   public static function redirectUrl()
   {
    return url("/admin/payment-with-method");
   }

    public function makePayment($trx, $amount, $user = []): string
    {
        $data = $this->getBodyForZiniPay($trx, $amount, $user);

        $response = Http::withHeaders([
            'zini-api-key'  => config('services.zinipay.api_key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.zinipay.url'), $data);

        return $response->body();
    }

    public function getBodyForZiniPay($trx, $amount, $user): array
    {
        return [
            'cus_name'     => $user['name'] ?? 'Null Null',
            'cus_email'    => $user['email'] ?? 'Null@Null.com',
            'amount'       => $amount,
            'redirect_url' => self::redirectUrl(),
            'cancel_url'   => self::redirectUrl(),
            'webhook_url'  => self::redirectUrl(),
            'metadata'     => [
                'trx'   => $trx,
                'phone' => $user['phone'] ?? '01234567890',
            ],
        ];
    }

}

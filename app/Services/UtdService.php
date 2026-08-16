<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UtdService
{
    protected $baseUrl;
    protected $apiKey;
    protected $projectId;
    protected $country;
    protected $currency;

    public function __construct()
    {
        $this->baseUrl = config('utd.base_url');
        $this->apiKey = config('utd.api_key');
        $this->projectId = config('utd.project_id');
    }

    public static function redirect_if_payment_success($trx)
    {
// (returns the app's own success URL)
        return url("/api/utd-success/$trx");
    }

    public function initiatePayment($trx, $amount, $user)
    {
        $body = $this->getBodyForutd($trx, $amount, $user);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl, $body);

        $json = $response->json();

        if ($response->successful() && $json['success']) {
            return $json['payUrl'];
        }

        return $json['error'];
    }

    protected function getBodyForutd($trx, $amount, $user): array
    {
        return [
            'apiKey' => $this->apiKey,
            'amount' => $amount,
            'currency' => 'USD',
            'userId' => (string)($user?->id ?? 'guest'),
            'userName' => $user?->name ?? 'Guest User',
            'userPhone' => $user?->phone ?? '',
            'userEmail' => $user?->email ?? '',
            'reference' => (string)$trx,
            'returnUrl' => self::redirect_if_payment_success($trx),
            'callbackUrl' => url('/api/utd-callback'),
        ];
    }

}

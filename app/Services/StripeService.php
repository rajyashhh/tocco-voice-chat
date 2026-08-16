<?php

namespace App\Services;

use App\Models\CoinLog;
use App\Models\Setting;
use Database\Seeders\config;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class StripeService {

    public function pay(array $settings, array $data): string
    {
        Stripe::setApiKey($settings['secret_key']);

        try {
            $amountInCents = intval($data['amount'] * 100);

            $session = StripeCheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $settings['currency'] ?? 'usd',
                        'product_data' => [
                            'name' => 'coins',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => $data['quantity'] ?? 1,
                ]],
                'mode' => 'payment',
                'success_url' => $settings['success_url'] . '?orderId=' . $data['order_id'],
                'cancel_url'  => $settings['cancel_url'],
                'metadata' => [
                    'user_id'  => $data['user_id'],
                    'order_id' => $data['order_id'],
                ],
            ]);

            CoinLog::where('id', $data['order_id'])
                ->update(['trx' => $session->id]);

                $endSession = \Stripe\Checkout\Session::retrieve($session->id);

                   $paymentIntentId = $endSession->payment_intent;
      

            return $session->url;

        } catch (\Exception $e) {
            throw new \Exception('Error generating payment link: ' . $e->getMessage());
        }
    }
}
    


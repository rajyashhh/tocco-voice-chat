<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\CoinLog;
use App\Models\Setting;
use App\Services\StripeService;
use App\Traits\User\PaymentTrait;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    use PaymentTrait;

    private const SUCCESS_STATUSES = ['succeeded', 'paid'];

    public function __construct(public StripeService $stripeService) {}
    public function pay(Request $request)
    {
        $request->validate([
            'quantity'     => 'required|integer|min:1',
            'coin_id'      => 'required|numeric'
        ]);
    
        try {
            $stripe_test_secret_key = Setting::where('key', 'stripe_test_secret_key')->first();
            $apiKey = $stripe_test_secret_key?->value;
    
            $coin = Coin::findOrFail($request->coin_id);
            $trx  = rand(111111111111111111, 999999999999999999);
    
            // إنشاء الطلب في النظام
            $order = CoinLog::query()->create([
                'coin_id'        => $coin->id,
                'paid_usd'       => $coin->usd,
                'user_id'        => auth()->id(),
                'obtained_coins' => $coin->coin,
                'method'         => 'card',
                'trx'            => $trx,
                'status'         => 0
            ]);
    
            $paymentRequest = new \Illuminate\Http\Request([
                'product_name' => $coin->coin,
                'amount'       => $coin->usd,
                'quantity'     => $request->quantity,
                'order_id'     => $order->id,
            ]);
    
            $link = $this->stripeService->pay($apiKey, $paymentRequest);
    
            return response()->json([
                'message' => 'Link generated successfully',
                'link'    => $link
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating payment link: ' . $e->getMessage(),
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function handleWebhook(Request $request)
    {


        $apiKey         = Setting::where('key', 'stripe_test_secret_key')->value('value');
        $endpointSecret = Setting::where('key', 'stripe_webhook_secret')->value('value');

        Stripe::setApiKey($apiKey);

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            [$orderId, $trxId, $status] = $this->extractStripeEventData($event);

            if (!$trxId && !$orderId) {
                return response('Ignored: no IDs', 200);
            }

            if (!in_array($status, self::SUCCESS_STATUSES)) {
                return response('Ignored: not successful', 200);
            }

            // Only checkout.session.completed carries our coin_log id (order_id
            // metadata); the intent/charge events expose only a payment_intent id
            // that never matches the coin_log we stored, so they cannot be mapped
            // to a claim and must not credit. Credit runs through the locked,
            // idempotent webhookPayment (same path as Codapay/PayPal/UTD). No
            // method filter: both Stripe entry points store the coin_log id in
            // metadata but under different methods ('card' vs 'strip').
            if ($orderId) {
                return $this->webhookPayment($orderId, newTrx: $trxId);
            }

            return response('Webhook Handled', 200);

        } catch (SignatureVerificationException $e) {
            return response('Invalid Signature', 400);

        } catch (\Exception $e) {
            return response('Webhook Error: ' . $e->getMessage(), 500);
        }
    }

    private function extractStripeEventData(object $event): array
    {
        $orderId = null;
        $trxId   = null;
        $status  = null;

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $orderId = $session->metadata->order_id ?? null;
                $trxId   = $session->payment_intent;
                $status  = $session->payment_status ?? null;
                break;

            case 'payment_intent.succeeded':
                $pi     = $event->data->object;
                $trxId  = $pi->id;
                $status = $pi->status;
                break;

            case 'charge.succeeded':
                $charge = $event->data->object;
                $trxId  = $charge->payment_intent ?? $charge->id;
                $status = $charge->status;
                break;

            case 'payment_intent.payment_failed':
            case 'payment_intent.canceled':
                $pi     = $event->data->object;
                $trxId  = $pi->id;
                $status = $pi->status;
                break;

            default:
        }

        return [$orderId, $trxId, $status];
    }

    public function success(Request $request)
    {

        try {
            $orderId = $request->get('orderId');

            $coin = CoinLog::find($orderId);
            return response()->json([
                'status'  => true,
                'trx'     => $coin->trx,
                'message' => 'Transaction completed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'failed', 'message' => 'Payment failed.',], 500);
        }
    }

    public function cancel(Request $request)
    {

        return response()->json([
            'status'  => false,
            'trx'     => '',
            'message' => 'Transaction cancelled.',
        ], 500);
    }

}

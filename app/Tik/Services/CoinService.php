<?php

namespace App\Tik\Services;

use App\Models\ShippingAgency;
use App\Services\CodapayService;
use App\Services\FawryPaymentServiceV2;
use App\Services\FawryService;
use App\Services\GooglePayService;
use App\Services\PaymobPaymentService;
use App\Services\PayPalService;
use App\Services\StripeService;
use App\Services\UtdService;
use App\Services\ZiniPaymentService;
use Exception;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FawryPaymentService;
use App\Classes\PaymentGateways\Fawry;
use App\Tik\Repositories\CoinRepository;
use App\Tik\Repositories\CoinLogRepository;
use App\Http\Controllers\Web\OPayController;
use App\Models\Setting;
use App\Tik\Repositories\PaymentCoinRepository;

class CoinService
{
    public function __construct(
        public StripeService $stripeService,
        private readonly CoinRepository $coinRepository,
        private readonly CoinLogRepository $coinLogRepository,
        private readonly PaymentCoinRepository $paymentCoinRepository,

    ) {}

    public function coinsList()
    {
        return $this->coinRepository->allCoins();
    }
    public function coins($payment_id)
    {
        return $this->coinRepository->allCoinsByPaymentId($payment_id);
    }

    public function buyCoins($request)
    {
        $coin = $this->coinRepository->findById($request->coin_id);
        if (!$coin) return Common::apiResponse(0, 'not found', null, 404);
        $paymentMethod = $coin->paymentCoin->type;
        $userType = $coin->paymentCoin->package_type;

        $user = $this->resolveCharger($request, $userType);


        $trx = rand(111111111111111111, 999999999999999999);
        // DB::beginTransaction();
        try {
            $dataCoinLog = [
                'paid_usd' => $coin->usd,
                'obtained_coins' => $coin->coin,
                'user_id' => $user->id,
                'method' => $paymentMethod,
                'trx' => $trx,
                'status' => 0,
                'coin_id' => $request->coin_id,
                'user_type' => get_class($user),
            ];
            $log = $this->coinLogRepository->create($dataCoinLog);
            //  DB::commit();
            $data = [
                'name' => $coin->coin . '_coins',
                'amount' => $coin->usd,
                'trx' => $log->trx,
                'order_id' => $log->id,
                'user_id' => $user->id
            ];
            if ($paymentMethod == 'strip') {

                $settings = $this->getStripeSettings();
                if (!$this->validateStripeSettings($settings)) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $sessionUrl = $this->createStripePayment($settings, $data);
                return Common::apiResponse(1, 'ok', $sessionUrl, 200);
            } elseif ($paymentMethod == 'fawry') {
                if (!$this->validateGateway('is_fawry_active', 'services.fawry', ['fawry_secret', 'fawry_merchant_code', 'fawry_return_url', 'fawry_url', 'fawry_webhook_url'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $newFawryService = new FawryPaymentServiceV2();
                $exterData = ["type" => 'charge_coin', 'paymentType' => "revenue"];

                $paymentUrl = $newFawryService->makePayment($log->id, $coin->usd, $exterData);
                if (isset($response['status']) && $paymentUrl['status']  == 0) {
                    return $paymentUrl;
                }
                return Common::apiResponse(1, 'ok', $paymentUrl, 200);
            } elseif ($paymentMethod == 'utd_fawry') {
                if (!$this->validateGateway('is_utd_fawry_active', 'services.utd_fawry', ['utd_fawry_secret', 'utd_fawry_merchant_code', 'utd_url', 'utd_fawry_return_url', 'utd_fawry_url'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $oldFawryService = new FawryPaymentService();
                $exterData = ["type" => 'charge_coin', 'paymentType' => "expenses"];

                $paymentUrl = $oldFawryService->makePayment($log->trx, $coin->usd, $exterData);
                if (isset($response['status']) && $paymentUrl['status']  == 0) {
                    return $paymentUrl;
                }
                return Common::apiResponse(1, 'ok', $paymentUrl, 200);
            } elseif ($paymentMethod == 'utd_paymob') {
                if (!$this->validateGateway('is_utd_paymob_active', 'services.utd_paymob', ['utd_paymob_secret', 'utd_paymob_merchant_code', 'utd_url', 'utd_paymob_return_url', 'utd_paymob_url'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $paymobService = new PaymobPaymentService();

                $paymentUrl = $paymobService->createPaymentLink(
                    amount: $coin->usd,
                    name: $user->name ?? 'User',
                    description: 'Charge Coin - ' . $log->trx,
                    email: $user->email ?? null,
                    phone: $user->phone ?? null,
                    trx: $log->trx
                );

                return Common::apiResponse(1, 'ok', $paymentUrl, 200);
            } else if ($paymentMethod == 'opay') {
                if (!$this->validateGateway('is_opay_active', 'nafezly-payments', ['OPAY_CURRENCY', 'OPAY_SECRET_KEY', 'OPAY_PUBLIC_KEY', 'OPAY_MERCHANT_ID', 'OPAY_COUNTRY_CODE', 'OPAY_BASE_URL', 'OPAY_WEBHOOK_URL'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $opay = new OPayController();
                return $opay->make($data, $user);
            } else if ($paymentMethod == 'zinipay') {
                if (!$this->validateGateway('is_zinipay_active', 'services.zinipay', ['api_key', 'url'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $ziniPayService = new ZiniPaymentService();
                return $ziniPayService->makePayment($log->id, $coin->usd, $user);
            } else if ($paymentMethod == 'paypal') {
                if (!$this->validateGateway('is_paypal_active', 'paypal', ['base_url', 'client_id', 'client_secret', 'currency', 'webhook_id'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                //                $paypalService = new PayPalService();
                //                $paymentLink = $paypalService->create($log->id, $coin->usd, $user);
                // $paymentLink = $paypalService->createOrder($log->id, $coin->usd, $user);
                $bladeUrl = url("/paypal/checkout/{$log->id}");

                return Common::apiResponse(1, 'ok', $bladeUrl, 200);
            } else if ($paymentMethod == 'google_pay') {
                if (!$this->validateGateway('is_google_pay_active', 'googlePay', ['payment_url', 'node_server_name'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $googlePayService = new GooglePayService();
                return $googlePayService->initiatePayment($log->id, $log->trx, $request->purchaseToken);
            } elseif ($paymentMethod == 'codapay') {
                if (!$this->validateGateway('is_codapay_active', 'codapay', ['base_url', 'api_key', 'project_id', 'country', 'pay_type', 'currency'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $codapayService = new CodapayService();

                $paymentUrl = $codapayService->initiatePayment($log->id, $coin->usd, $user->id);
                if (isset($response['status']) && $paymentUrl['status']  == 0) {
                    return $paymentUrl;
                }
                return Common::apiResponse(1, $paymentUrl, $paymentUrl, 200);
            } elseif ($paymentMethod == 'utd') {
                if (!$this->validateGateway('is_utd_active', 'utd', ['base_url', 'api_key', 'project_id', 'webhook_secret'])) {
                    return Common::apiResponse(0, __('This payment method is currently unavailable. Please choose another one.'), null, 400);
                }
                $utdService = new UtdService();

                $paymentUrl = $utdService->initiatePayment($log->id, $coin->usd, $user);
                if (isset($response['status']) && $paymentUrl['status']  == 0) {
                    return $paymentUrl;
                }
                return Common::apiResponse(1, 'ok', $paymentUrl, 200);
            } else {
                return Common::apiResponse(0, 'un supported payment gateway', null, 400);
            }
        } catch (Exception $exception) {
            //  DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }



    private function resolveCharger($request, string $userType)
    {
        switch ($userType) {
            case 'user':
                return $request->user();

            case 'shipping_agency':
                $charger = ShippingAgency::where('app_owner_id', Auth::id())->first();
                if (!$charger) {
                    return Common::apiResponse(0, 'Shipping agency not found', null, 404);
                }
                return $charger;

            default:
                return Common::apiResponse(0, 'Invalid type', null, 422);
        }
    }

    public function show($coinId)
    {
        return $this->coinRepository->findById($coinId);
    }

    public function create($request, $payment_id)
    {
        $data = [
            'usd'         => $request->usd,
            'coin'         => $request->coin,
            'payment_gateway_id' => $payment_id,
        ];

        $this->coinRepository->create($data);
        return true;
    }

    public function update($request)
    {
        $data = [
            'usd'         => $request->usd,
            'coin'         => $request->coin,
        ];
        $this->coinRepository->update($data, $request->coin_id);
        return true;
    }

    public function paymentCoin($type)
    {
        return $this->paymentCoinRepository->index($type);
    }

    public function createPaymentCoins($request)
    {
        $image = null;
        if ($request->hasFile('photo')) {
            $image = Common::upload('images', $request->file('photo'));
        }
        $data = [
            'photo' => $image,
            'title' => $request->title,
        ];
        $this->paymentCoinRepository->create($data);
        return true;
    }

    public function updatePaymentCoins($request)
    {
        $data = [
            'title' => $request->title,
        ];
        if ($request->hasFile('photo')) {
            $data['photo'] = Common::upload('images', $request->file('photo'));
        }

        $this->paymentCoinRepository->update($data, $request->payment_coin_id);
        return true;
    }

    public function showPayment($PaymentCoinId)
    {
        return $this->paymentCoinRepository->findById($PaymentCoinId);
    }

    public function getUserReport()
    {
        return $this->coinLogRepository->getUserCoinLogs();
    }

    public function getShippingAgencyReport($id)
    {
        return $this->coinLogRepository->getShippingAgencyCoinLogs($id);
    }


    private function validateGateway(string $activeKey, string $configGroup, array $requiredKeys): bool
    {
        if (!config($activeKey)) return false;

        foreach ($requiredKeys as $key) {
            if (empty(config("{$configGroup}.{$key}"))) return false;
        }

        return true;
    }

    private function getStripeSettings(): array
    {
        return [
            'secret_key'      => Setting::where('key', 'stripe_test_secret_key')->first()?->value,
            'cancel_url'      => Setting::where('key', 'stripe_cancel_url')->first()?->value,
            'success_url'     => Setting::where('key', 'stripe_success_url')->first()?->value,
            'currency'        => Setting::where('key', 'stripe_currency')->first()?->value,
            'is_active'       => Setting::where('key', 'is_strip_active')->first()?->value,
            'webhook_secret'  => Setting::where('key', 'stripe_webhook_secret')->first()?->value,
        ];
    }


    private function validateStripeSettings(array $settings): bool
    {
        return !(
            empty($settings['secret_key']) ||
            empty($settings['is_active']) ||
            empty($settings['currency']) ||
            empty($settings['webhook_secret'])
        );
    }


    private function createStripePayment(array $settings, $request)
    {

        return $this->stripeService->pay($settings, $request);
    }
}

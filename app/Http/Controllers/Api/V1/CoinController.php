<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentCoinResource;
use App\Models\ShippingAgency;
use Exception;
use App\Models\Coin;
use App\Helpers\Common;
use App\Models\CoinLog;
use Illuminate\Http\Request;
use App\Tik\Services\CoinService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Classes\PaymentGateways\Fawry;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Web\OPayController;

class CoinController extends Controller
{
    public function __construct(private CoinService $coinService) {}

    public function coinList(Request $request)
    {
        $user = $request->user();
        $data = $this->coinService->coinsList();
        return Common::apiResponse(1, (string) $user->di, $data, 200);
    }

    public function buyCoins(Request $request)
    {
        if (!$request->coin_id) return Common::apiResponse(0, 'missing param', null, 422);
        

        try {
            return  $this->coinService->buyCoins( $request );
        } catch (Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function index($payment_id)
    {
        $data = $this->coinService->coinsList($payment_id);
        return Common::apiResponse(1, '', $data);
    }

    public function store($payment_id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'usd'         => 'required|numeric',
            'coin'         => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->coinService->create($request, $payment_id);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "coin_id" => 'required|integer|exists:coins,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->coinService->show($request->coin_id);
        return Common::apiResponse(1, '', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usd'         => 'required|numeric',
            'coin'         => 'required|numeric',
            "coin_id" => 'required|integer|exists:coins,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->coinService->update($request);
            return Common::apiResponse(1, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function paymentCoin(Request $request)
    {
        $type = $request->type ?? 'user';

        if ($type === 'shipping') {
            $type = 'shipping_agency';
        }
        $data =  $this->coinService->paymentCoin($type);
        return Common::apiResponse(1, '',  PaymentCoinResource::collection($data));
    }

    public function createPaymentGateway(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'photo'         => 'required|mimes:jpeg,png,jpg,gif,svg',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->coinService->createPaymentCoins($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updatePaymentGateway(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'photo'         => 'required|mimes:jpeg,png,jpg,gif,svg',
            'payment_coin_id' => 'required|integer|exists:payment_coins,id'
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->coinService->updatePaymentCoins($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function showPaymentCoin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "payment_coin_id" => 'required|integer|exists:payment_coins,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->coinService->showPayment($request->payment_coin_id);
        return Common::apiResponse(1, '', $data);
    }


    public function userCoinReport()
    {
        try {
            $data = $this->coinService->getUserReport();
            return Common::apiResponse(1, 'User coin logs fetched successfully', $data);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function shippingAgencyCoinReport()
    {

        try {
            $id =request('id');
            $data = $this->coinService->getShippingAgencyReport($id);
            return Common::apiResponse(1, 'Shipping agency coin logs fetched successfully', $data);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }
}

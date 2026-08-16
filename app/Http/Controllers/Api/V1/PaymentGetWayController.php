<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\PaymentGatewayService;
use App\Http\Resources\Api\V1\PaymentResource;

class PaymentGetWayController extends Controller
{
    public function __construct(private PaymentGatewayService $paymentGatewayService) {}
    public function index()
    {
        $data = $this->paymentGatewayService->index();
        return Common::apiResponse(1, '', PaymentResource::collection($data), 200);
    }

    public function selectPaymentGateway(Request $request)
    {
        $user = $request->user();
        if (($user->type_user != 4) && ($user->type_user != 3)) {
            return Common::apiResponse(0, __('api.notShippingAgent'), 422);
        }
        $user->paymentGateways()->sync($request->paymentGateway_id);
        return Common::apiResponse(1, 'success');
    }
}

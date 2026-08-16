<?php

namespace App\Http\Controllers\Api\V1;


use Exception;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\RequestBackgroundImagService;




class RequestBackgroundImageController extends Controller
{

    protected $requestBackgroundImagService;

    public function __construct(RequestBackgroundImagService $requestBackgroundImagService)
    {
        $this->requestBackgroundImagService = $requestBackgroundImagService;
    }


    public function RequestBackgroundImage(Request $request)
    {
        $user                  = $request->user();
        $costRequestBackGround = Common::getConfig('cost_request_background');
        if (!$costRequestBackGround) {
            return Common::apiResponse(0, 'not found params (cost_request_backround) in config dashboard', null, 404);
        }
        if ($user->di < $costRequestBackGround) {
            return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);
        }
        if ($request->image == null) return Common::apiResponse(0, 'missing param', null, 422);

        try {
            $image = $this->requestBackgroundImagService->create($request, $user->id, $costRequestBackGround);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        return Common::apiResponse(1, 'done', ['image' => $image], 200);
    }




}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\BackgroundService;
use Illuminate\Support\Facades\Validator;
use App\Tik\Services\RequestBackgroundImagService;



class BackgroundController extends Controller
{

    protected $backgroundService;

    public function __construct(
        BackgroundService $backgroundService,
        private RequestBackgroundImagService $requestBackgroundImagService
    ) {
        $this->backgroundService = $backgroundService;
    }


    public function roomBackground()
    {
        $data = $this->backgroundService->index();
        return Common::apiResponse(1, '', $data);
    }


    public function allBackgrounds(Request $request)
    {
        $data = $this->backgroundService->index();
        return Common::apiResponse(1, Common::getConfig('cost_request_background'), $data, 200);
    }

    public function backgroundSetting(Request $request)
    {
        $data = [
            'cost' => Common::getConfig('cost_request_background'),
            'expire' => Common::getConfig('background_expiration'),
        ];
        return Common::apiResponse(1, '', $data, 200);
    }

    public function allMyBackgrounds(Request $request)
    {
        $user = $request->user();
        $costRequestBackGround = Common::getConfig('cost_request_background');
        $data = $this->requestBackgroundImagService->findByUserId($user->id);
        return Common::apiResponse(1, $costRequestBackGround, $data, 200);
    }
}

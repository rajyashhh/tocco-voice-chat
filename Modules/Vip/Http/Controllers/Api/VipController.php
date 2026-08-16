<?php

namespace Modules\Vip\Http\Controllers\Api;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\Vip\Services\Api\VipService;
use Modules\Vip\Http\Resources\VipResource;
use Modules\Vip\Http\Requests\BadgesRequest;
use Modules\Vip\Http\Resources\OVipResource;
use Modules\Vip\Traits\HandlesApiExceptions;
use Modules\Vip\Http\Resources\OVipNewResource;
use Modules\Vip\Http\Requests\DeleteWareRequest;
use Modules\Vip\Http\Requests\GetWareVipRequest;
use Modules\Vip\Http\Resources\BackgroundResource;
use Modules\Vip\Http\Requests\CreateWareVipRequest;
use Modules\Public\Http\Services\UserCounterServices;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Modules\Vip\Http\Requests\BuyVipPercentageRequest;



class VipController extends Controller
{
    use HandlesApiExceptions;

    public function __construct(private VipService $vipService) {}

    public function index()
    {
        $type = request('type', 2);
        $vips = $this->vipService->vipIndex($type);

        return Common::apiResponse(
            true,
            'VIP list fetched successfully.',
            VipResource::collection($vips)
        );
    }

    public function background()
    {
        $vips = $this->vipService->backgroundImage();

        return Common::apiResponse(
            true,
            'successfully fetched.',
            BackgroundResource::collection($vips)
        );
    }

    public function vipList()
    {
        $data = $this->vipService->vipList();
        request()->merge(['vipPrivileges' => $data['all_privileges']]);

        return Common::apiResponse(
            true,
            'VIP options loaded.',
            OVipResource::collection($data['o_vips'])
        );
    }
    public function vipUserList(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->vipService->vipUserList($userId);

        request()->merge(['vipPrivileges' => $data['all_privileges']]);

        return Common::apiResponse(
            true,
            'User VIPs fetched.',
            OVipNewResource::collection($data['o_vips'])
        );
    }

    public function buyVip(Request $request)
    {
        if (!$request->vip_id) {
            return Common::apiResponse(false, 'Missing vip_id', null, 422);
        }

        return $this->wrap(function () use ($request) {
            [$user, $countWares, $buyer, $exp] = $this->vipService->buyVip($request);
            (new UpgradeLevelServices())->buyAristocracy($buyer, $exp);
            (new UserCounterServices())->eventUser($user, 'mybag', $countWares);
            return Common::apiResponse(true, 'Done', null, 201);
        });
    }

    public function vip_use(Request $request)
    {
        if (!$request->vip_id) {
            return Common::apiResponse(false, __('api_responses.missing_params'), null, 422);
        }

        return $this->wrap(fn() => Common::apiResponse(true, 'Success', $this->vipService->userVip($request)));
    }

    public function pack_use(Request $request)
    {
        if (!$request->pack_id) {
            return Common::apiResponse(false, __('api_responses.missing_params'), null, 422);
        }

        return $this->wrap(fn() => Common::apiResponse(true, 'Success', $this->vipService->usePack($request)));
    }

    public function vip_send(Request $request)
    {
        if (!$request->user_id || !$request->vip_id) {
            return Common::apiResponse(false, __('api_responses.missing_params'), null, 422);
        }

        return $this->wrap(fn() => Common::apiResponse(true, 'Success', $this->vipService->sendVip($request)));
    }

    public function createWareVip(CreateWareVipRequest $request)
    {
        return $this->wrap(function () use ($request) {
            $this->vipService->createWareVip($request);
            return Common::apiResponse(true, 'Created successfully');
        });
    }

    public function getWareVip(GetWareVipRequest $request)
    {
        return $this->wrap(fn() => Common::apiResponse(true, 'success', $this->vipService->wareVip($request)));
    }

    public function deleteWare(DeleteWareRequest $request)
    {
        return $this->wrap(function () use ($request) {
            $this->vipService->deleteWare($request->ware_id);
            return Common::apiResponse(true, 'Deleted successfully');
        });
    }

    public function badges(BadgesRequest $request)
    {
        return $this->wrap(function () use ($request) {
            $this->vipService->badges($request->type);
            $groups = $this->vipService->getLevelGroups();
            return Common::apiResponse(true, 'success', $groups);
        });
    }

    public function roomBadges()
    {
        return $this->wrap(function () {
            $this->vipService->badges(4);
            $groups = $this->vipService->getRoomLevel();
            return Common::apiResponse(true, 'success', $groups);
        });
    }

    public function buyVipPercentage(BuyVipPercentageRequest $request)
    {
        return $this->wrap(fn() => $this->vipService->buyVips($request));
    }
}

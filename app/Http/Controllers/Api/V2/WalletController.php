<?php

namespace App\Http\Controllers\Api\V2;

use Carbon\Carbon;
use App\Models\Pack;
use App\Models\Ware;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\MallService;
use Modules\Vip\Entities\UserVip;
use App\Http\Controllers\Controller;
use App\Http\Resources\WareResource;
use App\Http\Resources\WareResourceAll;
use App\Http\Resources\WarePaddingResource;
use App\Http\Resources\BestWareSaleResource;

use App\Tik\Services\WalletStatisticService;


class WalletController extends Controller
{
    public function __construct(private WalletStatisticService $walletStatisticService) {}

    public function diamondsStatistic(Request $request)
    {
        $user = $request->user();
        $data = $this->walletStatisticService->diamondsStatistic($user->id, $request->type, $request->startDate, $request->endDate, $request->perPage, $request->page);
        return Common::apiResponse(true, '', $data, 200, null, 'list');
    }

 
}

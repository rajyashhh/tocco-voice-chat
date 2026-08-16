<?php

namespace Modules\CP\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\GiftLog;
use App\Models\Pack;
use App\Models\Ware;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CP\Entities\CpRelation;
use Illuminate\Support\Facades\Validator;
use Modules\CP\Entities\Cp;
use Modules\CP\Entities\UserRelationAvilable;
use Modules\CP\Http\Resources\CpLevelResource;
use Modules\CP\Http\Services\CpProfileService;
use Modules\CP\Http\Services\CpserviceCo;
use Modules\CP\Http\Services\ExtendCardService;
use Modules\CP\Transformers\CpListResource;
use Modules\CP\Transformers\RankingResource;
use Modules\CP\Transformers\RequestCpResource;

class CpController extends Controller
{
    protected $cpService, $extendCardService, $cpProfileService;

    public function __construct(CpserviceCo $cpService, ExtendCardService $extendCardService, CpProfileService $cpProfileService)
    {
        $this->cpService = $cpService;
        $this->extendCardService = $extendCardService;
        $this->cpProfileService = $cpProfileService;
    }
    public function cpLevels()
    {
        $type = request('type') ?? 'lovely';
        $levelsIds = Cp::where(function ($q) {
            $q->where('user_one_id', Auth::id())
                ->orWhere('user_two_id', Auth::id());
        })
            ->whereHas('relation', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->pluck('level_id')
            ->toArray();

        $cp_relations = CpRelation::with('levels.gifts')
            ->where('type', $type)
            ->first();

        $result = [];
        if (!$cp_relations) return Common::apiResponse(1, 'not found', $result);

        foreach ($cp_relations->levels as $level) {
            $have = in_array($level->id, $levelsIds);

            // تصنيف الهدايا وتجهيز البيانات
            $levelGifts = [];
            foreach ($level->gifts as $gift) {
                $image = null;
                switch ($gift->type) {
                    case 'ware':
                        $image = $gift->ware->show_img;
                        break;
                    case 'vip':
                        $image = $gift->vip->img;
                        break;
                    case 'coins':
                        $image ='custom_image/gold_coin_icon.png';
                        break;
                    case 'acheivment':
                        $image = $gift->item_id;
                        break;
                }

                if ($image) {
                    if (!isset($levelGifts[$gift->type])) {
                        $levelGifts[$gift->type] = [
                            'title' => $gift->type,
                            'images' => [],
                        ];
                    }
                    $levelGifts[$gift->type]['images'][] = $image;
                }
            }

            $levelGifts = array_values($levelGifts);

            $data = [
                'level' => $level->level,
                'title' => $level->name_en,
                'have' => $have,
                'gifts' => $levelGifts,
            ];

            $result[] = $data;
        }

        return Common::apiResponse(1, '', $result);
    }



    public function makeRequestCp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'cp_relation_id' => 'required|exists:cp_relations,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(',', $validator->errors()->all());
            return Common::apiResponse(0, $errors);
        }

        $user = $request->user();
        return $this->cpService->makeRequestCp($request, $user);
    }

    public function getRequestCp()
    {
        $user = Auth::user();
        return $this->cpService->getRequestCp($user);
    }

    public function RespondRequest(Request $request)
    {
        return $this->cpService->respondToRequest($request);
    }

    public function CpRanking()
    {
        return $this->cpService->getCpRanking();
    }

    public function cpList()
    {
        $userId = Auth::id();
        return $this->cpService->getCpList($userId);
    }


    public function cpUserList()
    {
        $userId = request('user_id') ?? Auth::id();
        return $this->cpService->cpUserList($userId);
    }

    public function extendCard(Request $request)
    {
        $user = Auth::user();
        return $this->extendCardService->extendCard($user, $request->ware_id);
    }


    public function cpProfile()
    {
        $userId = request('user_id') ?? Auth::id();
        return $this->cpProfileService->getCpProfiles($userId);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;


use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\CharismaLevelResource;
use App\Models\CharismaLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CharismaLevelController extends Controller
{


public function index(Request $request)
    {
        $charismaLevels = Cache::rememberForever('charisma_levels', function () {
            return CharismaLevel::orderBy('level', 'asc')->get();
        });

        return Common::apiResponse(true, 'success', CharismaLevelResource::collection($charismaLevels));
    }

}
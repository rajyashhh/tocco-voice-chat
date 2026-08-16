<?php

namespace Modules\RoomBoom\Http\Controllers\Api;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RoomBoom\Entities\SuperBoomRule;
use Modules\RoomBoom\Http\Resources\RoomBoomRuleResource;

class SuperBoomRuleController extends Controller
{
    public function index()
    {
        $data = SuperBoomRule::all();
        return Common::apiResponse(true, '', RoomBoomRuleResource::collection($data), 200);

    }

   
}

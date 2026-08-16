<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Models\GiftCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\GiftCategoryResource;
use Illuminate\Support\Facades\Cache;


class GiftCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Performance fix: cache gift categories for 30 minutes — data rarely changes
        $giftCategories = Cache::remember('gift_categories:api', 1800, function () {
            return GiftCategory::orderBy('sort', 'asc')->get();
        });
        return Common::apiResponse(1, '', GiftCategoryResource::collection($giftCategories));
    }
}

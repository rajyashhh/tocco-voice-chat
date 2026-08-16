<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Tik\Services\RoomCategoryService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\RoomCategoryResource;



class RoomCategoryController extends Controller
{
    public function __construct(private RoomCategoryService $roomCategoryService)
    {
    }

    public function allClasses()
    {
        $data = $this->roomCategoryService->index();
        return Common::apiResponse(1, '', $data);
    }

    public function getClassChildren($id)
    {
        $class = $this->roomCategoryService->findById($id);
        if ($class) {
            return Common::apiResponse(1, '', $class->children);
        }
        return Common::apiResponse(0, 'not found', null, 404);
    }

    public function getTypes()
    {
        $data = $this->roomCategoryService->getByType();
        return Common::apiResponse(1, '', RoomCategoryResource::collection($data), 200);
    }
}

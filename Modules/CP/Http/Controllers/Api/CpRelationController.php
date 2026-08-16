<?php

namespace Modules\CP\Http\Controllers\Api;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CP\Entities\CpRelation;
use Modules\CP\Transformers\CpRelationResource;

class CpRelationController extends Controller
{

    public function index()
    {
        $data = CpRelation::select("id","title","image","price")->get();

        // TODO add user available Cards count and convert this to resource
        return Common::apiResponse(1, '', CpRelationResource::collection($data));
    }

}

<?php

namespace Modules\ServerControl\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\Config;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConfigControlController extends Controller
{
    public function index()
    {
        $configs = Config::get();
        return Common::apiResponse(1, '', $configs, 200);
    }

   
    public function create()
    {
        return view('servercontrol::create');
    }

    public function store(Request $request)
    {
        if (!$request->name || !$request->value ) {
            return Common::apiResponse(0, __("api_responses.missing_params"), null, 422);
        }
        Config::create([
            'name'  => $request->name,
            'value' => $request->value,
            'desc' => $request->desc,
        ]);
        return Common::apiResponse(1,__("api_responses.success") ,[], 200);
    }

    public function update(Request $request, $id)
    {
        $config = Config::find($id);
        if (!$config) {
            return Common::apiResponse(0, __("api_responses.not_found"), null, 422);
        }
        if ($request->name) {
           $config->name = $request->name; 
        }

        if ($request->value) {
           $config->value = $request->value; 
        }

        if ($request->desc) {
           $config->desc = $request->desc; 
        }
       
        if ($request->is_hidden) {
           $config->is_hidden = $request->is_hidden; 
        }

        $config->save();
        return Common::apiResponse(1,__("api_responses.updated") ,[], 200);
    }

    public function destroy($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return Common::apiResponse(0, __("api_responses.not_found"), null, 422);
        }
        $config->delete();
        return Common::apiResponse(1,__("api_responses.deleted") ,[], 200);
    }
}

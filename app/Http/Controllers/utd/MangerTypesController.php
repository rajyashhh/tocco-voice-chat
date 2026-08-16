<?php

namespace App\Http\Controllers\utd;

use App\Models\Config;
use Exception;
use App\Helpers\Common;
use App\Models\ImageColor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\MangerTypesService;
use Illuminate\Support\Facades\Validator;

class MangerTypesController extends Controller
{

    public function __construct(private MangerTypesService $MangerTypesService) {}

    public function all(Request $request)
    {
        $data = $this->MangerTypesService->all($request->id);
        return Common::apiResponse(true, 'done', $data);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'files' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        

        try {
            $this->MangerTypesService->create($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'files' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->MangerTypesService->update($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        $data = $this->MangerTypesService->show($id);
        return Common::apiResponse(true, 'done', $data);
    }

    public function destroy($id)
    {
        try {
            $this->MangerTypesService->delete($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function search( $id,Request $request)
    {

        try {
            $reel = $this->MangerTypesService->search($id);
            return Common::apiResponse(true, 'success', $reel);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
     
    }

    public function config($num ,Request $request)
    {
        $conf = Config::where('name','upload_moment')->first();
        if(!$conf)
        {
            config::create([
                'name'  => 'upload_moment',
                'value' => $num,
            ]);
        }else{
            $conf->value = $num;
            $conf->save();
        }
        return Common::apiResponse(true, __('dashboard.update'), null);


    }
}

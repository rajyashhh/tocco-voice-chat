<?php

namespace App\Http\Controllers\utd;

use App\Http\Resources\BlockedPersonsListResource;
use App\Http\Resources\UserBlacksListResource;
use App\Models\BlackList;
use App\Tik\Services\BlackListService;
use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\AgencyService;
use App\Tik\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ActiveAgencyResource;
use App\Http\Resources\AgencyRequestsResource;


class BlackListController extends Controller
{
    public function __construct(private BlackListService $service) {}

    public function list()
    {
        try {
            $key =request('key') ?? null;
            $perPage =request('per_page');
            $page = request('page');
            $data = $this->service->list($key ,$perPage,$page);
            return Common::apiResponse(true, 'done', UserBlacksListResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    // public function search($key)
    // {
    //     try {
    //         $data = $this->service->search($key);
    //         return Common::apiResponse(true, 'done', UserBlacksListResource::collection($data));
    //     } catch (Exception $exception) {

    //         return Common::apiResponse(0, $exception->getMessage(), null, 400);
    //     }
    // }

    // public function blocked_search($user_id,$key)
    // {
    //     try {
    //         $data = $this->service->blocked_search($user_id,$key);
    //         return Common::apiResponse(true, 'done', BlockedPersonsListResource::collection($data));
    //     } catch (Exception $exception) {

    //         return Common::apiResponse(0, $exception->getMessage(), null, 400);
    //     }
    // }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'from_uid' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
          
            $data = $this->service->store($validator->validated());
            return Common::apiResponse(true, 'done',);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function black_lists($user_id)
    {
        try {
            $key =request('key') ?? null;
            $perPage =request('per_page');
            $page = request('page');
            $data = $this->service->black_lists($user_id,$key,$perPage,$page);
            return Common::apiResponse(true, 'done', BlockedPersonsListResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
    
    public function delete($id)
    {
        try {
            $data = BlackList::findOrFail($id);
            $delete = $data->delete() ;
            return Common::apiResponse(true, 'done' );
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
    
}

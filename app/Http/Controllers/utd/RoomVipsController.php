<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RoomVipResource;
use App\Tik\Services\RoomVipsService;
use Modules\Reals\Entities\Real;
use App\Tik\Services\ReelsService;
use Illuminate\Http\Request;
use Exception;

class RoomVipsController extends Controller
{
    public function __construct(private RoomVipsService $roomVipsService) {}
    public function index(Request $request)
    {

        try {
            $roomVips = $this->roomVipsService->index($request->per_page, $request->Page);
            return Common::apiResponse(true, 'success',RoomVipResource::collection($roomVips));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

    
    }
    public function show( $id,Request $request)
    {

        try {
            $roomVip = $this->roomVipsService->show($id);
            return Common::apiResponse(true, 'success', $roomVip);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
     
    }

    public function search( $key,Request $request)
    {

        try {
            $roomVip = $this->roomVipsService->search($key);
            return Common::apiResponse(true, 'success', $roomVip);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
     
    }


    


    public function destroy($id)
    {
        try {
            $roomVip = $this->roomVipsService->delete($id);
            return Common::apiResponse(true, 'success', null);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
       

    }
    public function store(Request $request){

     
        try {
           $data = $this->roomVipsService->store($request);
            return Common::apiResponse(true, 'created successfully',$data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

    }

    public function update(Request $request, $id){

      
        $roomVip = $this->roomVipsService->show($id);
        if (!$roomVip) throw new \Exception('not found');

        if ($request->name_en) {
            $roomVip->name_en = $request->name_en;
        }
        if ($request->name_ar) {
            $roomVip->name_ar = $request->name_ar;
        }
        if ($request->exp) {
            $roomVip->exp = $request->exp;
        }
        if ($request->co) {
            $roomVip->co = $request->co;
        }
        if ($request->di) {
            $roomVip->di = $request->di;
        }
        if ($request->hasFile('img')) {
            $roomVip->img = Common::upload('RoomVips', $request->file('img'));
        }
        $roomVip->save();

        return Common::apiResponse(1, 'Room Vip updated successfully');


    }
}

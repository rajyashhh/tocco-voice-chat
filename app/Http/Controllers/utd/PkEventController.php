<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;
use App\Http\Resources\PkEventResource;
use Illuminate\Support\Facades\Validator;

class PkEventController extends Controller
{

    public function index(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = PkEvent::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d',

        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $lastStartDate = PkEvent::max('start_date');
            $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addWeek()->toDateString() : null;
            if ($minStartDate == $request->start_date) {
                return Common::apiResponse(0, __('date must be after ' . $minStartDate),);
            }

            PkEvent::create($request->all());
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function defaultDate()
    {
        $lastStartDate = PkEvent::max('start_date');

        $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addDay(8)->toDateString() : null;
        return Common::apiResponse(true, 'done', $minStartDate);
    }

    public function show($id)
    {
        try {
            $data = PkEvent::findOrFail($id);
            return Common::apiResponse(true, ' successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d',

        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $data = PkEvent::findOrFail($id);
            $data->update($request->all());
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $data = PkEvent::findOrFail($id);
            $data->delete();
            return Common::apiResponse(true, 'deleted successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allGifts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|integer|in:1,2,3',
            'pk_event_id' => 'required|integer|exists:pk_events,id',
            'pk_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = PkReward::where('pk_event_id', $request->pk_event_id)->where("pk_type", $request->pk_type)->where('level', $request->level)->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->with('ware', 'vip')->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', PkEventResource::collection($data));
    }

    public function storeGift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pk_event_id' => 'required|integer|exists:pk_events,id',
            'pk_type' => 'required|string',
            'level' => 'required|numeric|in:1,2,3',
            'type' => 'required',
            'target1' => 'nullable',
            'target2' => 'nullable',
            'target3' => 'nullable',
            'expire'  => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            if ($request->hasFile('target4')) {
                $target = Common::upload('images', $request->file('target4'));
            }

            $data = [
                'pk_event_id' => $request->pk_event_id,
                'pk_type' => $request->pk_type,
                'level' => $request->level,
                'type' => $request->type,
                'expire'  => $request->expire,
            ];
            PkReward::create($data);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateGift($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pk_event_id' => 'required|integer|exists:pk_events,id',
            'pk_type' => 'required|string',
            'level' => 'required|numeric|in:1,2,3',
            'type' => 'required',
            'target1' => 'nullable',
            'target2' => 'nullable',
            'target3' => 'nullable',
            'expire'  => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            if ($request->hasFile('target4')) {
                $target = Common::upload('images', $request->file('target4'));
            }

            $data = [
                'pk_event_id' => $request->pk_event_id,
                'pk_type' => $request->pk_type,
                'level' => $request->level,
                'type' => $request->type,
                'expire'  => $request->expire,
            ];
            $reward =  PkReward::findOrFail($id);
            if ($request->type == 'ware') {
                $reward->target = $request->target1;
            } elseif ($request->type == 'vip') {
                $reward->target = $request->target2;
            } elseif ($request->type == 'coins') {
                $reward->target = $request->target3;
            } elseif ($request->type == 'achievement' && $request->hasFile('target4')) {
                $file = $request->file('target4');
                $reward->target = Common::upload('images', $file);
            }
            $reward->update($data);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroyGift($id)
    {
        try {
            $data =  PkReward::findOrFail($id);
            $data->delete();
            return Common::apiResponse(true, 'deleted successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function showGift($id)
    {
        try {
            $data =  PkReward::with('ware', 'vip')->findOrFail($id);
            return Common::apiResponse(true, 'done', new PkEventResource($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

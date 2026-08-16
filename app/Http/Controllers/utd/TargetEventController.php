<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Events\Entities\RewardTarget;
use App\Http\Resources\TargetEventResource;
use Modules\Events\Entities\ChargeTargetEvent;

class TargetEventController extends Controller
{

    public function index(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = ChargeTargetEvent::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->select('id', 'value')->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            ChargeTargetEvent::create($request->all());
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        try {
            $data =  ChargeTargetEvent::select('id', 'value')->findOrFail($id);
            return Common::apiResponse(true, ' successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $data =  ChargeTargetEvent::findOrFail($id);
            $data->update($request->all());
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $data =  ChargeTargetEvent::findOrFail($id);
            $data->delete();
            return Common::apiResponse(true, 'deleted successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allGifts($targetId, Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = RewardTarget::where('charge_event_id', $targetId)->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->with('ware', 'vip')->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', TargetEventResource::collection($data));
    }

    public function storeGift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charge_event_id' => 'required|integer|exists:reward_charges,id',
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
                'charge_event_id' => $request->charge_event_id,
                'type' => $request->type,
                'expire'  => $request->expire,
            ];
            RewardTarget::create($data);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateGift($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charge_event_id' => 'required|integer|exists:reward_charges,id',
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
                'charge_event_id' => $request->charge_event_id,
                'type' => $request->type,
                'expire'  => $request->expire,
            ];
            $reward =  RewardTarget::findOrFail($id);
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
            $data =  RewardTarget::findOrFail($id);
            $data->delete();
            return Common::apiResponse(true, 'deleted successfully',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function showGift($id)
    {
        try {
            $data =  RewardTarget::with('ware', 'vip')->findOrFail($id);
            return Common::apiResponse(true, 'done', new TargetEventResource($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

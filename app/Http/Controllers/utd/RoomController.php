<?php

namespace App\Http\Controllers\utd;

use App\Helpers\WebPHelper;
use Exception;
use App\Models\Room;
use App\Helpers\Common;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{

    public function all(Request $request)
    {
        $input = $request->search;
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        $data = Room::with('owner')->when(isset($id), function ($query) use ($input) {
            $query->whereHas('owner', function ($query) use ($input) {
                $query->where('name', 'like', "%$input%")
                    ->orWhere('uuid', 'like', "%$input%");
            });
        })->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->orderByDesc('rooms.pin')
            ->orderByDesc('rooms.top_room')
            ->orderByDesc('session')
            ->orderByDesc('count_room_socket')->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, '', RoomResource::collection($data), 200);
    }

    public function updateSwitches(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'         => 'required|integer|exists:rooms,id',
            'key' => 'required|string|in:room_status,top_room,pin,is_afk',
            'value' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {

            $room = Room::findOrFail($request->room_id);
            $room->update([$request['key'] => $request['value'],]);

            return Common::apiResponse(true, 'changed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numid' => 'nullable|integer',
            'room_status' => 'required|boolean',
            'top_room' => 'required|boolean',
            'pin' => 'required|boolean',
            'max_admin' => 'nullable|integer',
            'room_name' => 'nullable|string',
            'room_cover' => 'nullable|mimes:jpeg,png,jpg,gif',
            'room_intro' => 'nullable|string',
            'room_pass' => 'nullable',
            'room_class' => 'nullable:integer',
            'sort_num' => 'required|integer',
            'room_type' => 'nullable|integer',
            'room_welcome' => 'nullable|string',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        $data = [
            'numid'  => $request->numid,
            'room_status' => $request->room_status,
            'top_room' => $request->top_room,
            'pin' => $request->pin,
            'max_admin' => $request->max_admin,
            'room_name' => $request->room_name,
            'room_intro' => $request->room_intro,
            'room_pass' => $request->room_pass,
            'room_class' => $request->room_class,
            'sort_num' => $request->sort_num,
            'room_type' => $request->room_type,
            'room_welcome' => $request->room_welcome,

        ];
        if ($request->hasFile('room_cover')) {

              $data['room_cover'] = WebPHelper::uploadWebp(
                        $request->file('room_cover'),
                        'images',
                        'room_cover',
                        async: true  // Convert to WebP asynchronously
                 );
        }

        try {
            Room::create($data);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numid' => 'nullable|integer',
            'room_status' => 'required|boolean',
            'top_room' => 'required|boolean',
            'pin' => 'required|boolean',
            'max_admin' => 'nullable|integer',
            'room_name' => 'nullable|string',
            'room_cover' => 'nullable',
            'room_intro' => 'nullable|string',
            'room_pass' => 'nullable',
            'room_class' => 'nullable:integer',
            'sort_num' => 'required|integer',
            'room_type' => 'nullable|integer',
            'room_welcome' => 'nullable|string',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        $data = [
            'numid'  => $request->numid,
            'room_status' => $request->room_status,
            'top_room' => $request->top_room,
            'pin' => $request->pin,
            'max_admin' => $request->max_admin,
            'room_name' => $request->room_name,
            'room_intro' => $request->room_intro,
            'room_pass' => $request->room_pass,
            'room_class' => $request->room_class,
            'sort_num' => $request->sort_num,
            'room_type' => $request->room_type,
            'room_welcome' => $request->room_welcome,

        ];
        if ($request->hasFile('room_cover')) {

            $data['room_cover'] = WebPHelper::uploadWebp(
                        $request->file('room_cover'),
                        'images',
                        'room_cover',
                        async: true  // Convert to WebP asynchronously
                 );
        }

        try {
            $room = Room::findOrFail($id);
            $room->update($data);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        try {
            $room = Room::findOrFail($id);
            return Common::apiResponse(true, '', $room, 200);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function roomClass()
    {
        $data = RoomCategory::query()->select('id', 'name')->where('enable', 1)->where('parent_id', 0)->get();
        return Common::apiResponse(true, '', $data, 200);
    }

    public function roomType($roomClassId)
    {
        $data = RoomCategory::select('id', 'name')->where('parent_id', $roomClassId)->where('enable', 1)->get();
        return Common::apiResponse(true, '', $data, 200);
    }

    public function destroy($id)
    {
        try {
            $room = Room::findOrFail($id);
            $room->delete();
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

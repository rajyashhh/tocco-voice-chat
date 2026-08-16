<?php

namespace Modules\Charizma\Http\Controllers;

use App\Models\Room;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Log;
use Modules\Achievement\Http\Services\UserAchievementService;


class CharizmaController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('charizma::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('charizma::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('charizma::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('charizma::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    // public function enable_carizma(Request $request){
    //     $rooms = Room::all();
    //     return $rooms;
    // }

    /**
     * @param int $owner_id
     * @return JsonResponse
     */


    /**
     * Toggle the room's charisma visibility flag. Charisma is now
     * SERVER-AUTHORITATIVE: the backend owns the per-room cumulative totals in
     * Redis (RoomCharismaStore). This endpoint flips the rooms column, clears
     * the per-room charisma store (a toggle — on OR off — starts a fresh round),
     * and broadcasts the start/close cue so clients re-render from a clean slate.
     */
    public function changeStatus(Request $request): JsonResponse
    {
        $roomId = $request->room_id;
        $room = Room::find($roomId);

        if (!$room) {
            return Common::apiResponse(0, __('api_responses.room_not_found'), null, 404);
        }

        $room->charizma_status = !$room->charizma_status;
        $isFalse = $room->charizma_status == 0;
        $room->charizma_timestamp = $isFalse ? null : now()->timestamp;
        $room->save();

        // Reset the per-room charisma store on every toggle: turning OFF clears
        // stale totals; turning ON starts the new round at zero.
        \App\Services\RoomCharismaStore::reset((int) $room->id);

        $ms = [
            'messageContent' => [
                "message" => $room->charizma_status ? 'startCharisma' : 'closeCharisma',
            ]
        ];
        $json = json_encode($ms);
        Common::sendToStream('SendCustomCommand', $roomId, Auth::id(), $json);

        return Common::apiResponse(
            1,
            'charisma status is changed',
            [
                "room_id" => $room->id,
                "charisma_status" => $room->charizma_status
            ],
            200
        );
    }
}

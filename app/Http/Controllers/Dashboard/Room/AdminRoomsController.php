<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Room\AdminRoomsResource;
use App\Models\Room;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminRoomsController extends Controller
{
    use DashBoardTrait;
    public function index($type)
    {
        if($type == 'all')
        {

            $data = Room::all();
        }
        else if($type == 'PK')
        {
            $data = Room::where('is_show_pk',1)->get();
        }
        else if($type == 'Cinema')
        {
            $data = Room::where('mode',3)->get();
        }
        else if($type == 'Lock')
        {
            $data = Room::whereNotNull('room_pass')->get();
        }
        return AdminRoomsResource::collection($data);
    }

    public function enable_rooms(Request $request, $id , $status , $type)
    {
        $Room = Room::find($id);
        if($Room)
        {
            if($type == 'room_status')
            {
                $Room->room_status = $status  == 'true' ? 1 : 0;
            }
            else if($type == 'top_room')
            {
                $Room->top_room = $status  == 'true' ? 1 : 0;
            }
            else{
                $Room->pin  = $status  == 'true' ? 1 : 0;
            }
            $Room->update() ;
            return [$Room->room_status , $id , $status , $type];
        }
        return response()->json([
            'status' => 200,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_status'      => 'required|max:255',
            'top_room'         => 'required|max:255',
            'pin'              => 'required|max:255',
            'max_admin'        => 'required|max:255',
            'name'             => 'required|max:255',
            'intro'            => 'nullable|max:255',
            'password'         => 'nullable|max:255',
            'show_img'         => 'nullable',
            'background_id'    => 'nullable|exists:backgrounds,id',
            'owner_id'         => 'required|exists:users,id|unique:rooms,uid',
        ]);
        do { $randomUserId = rand(1000000, 9000000); } while (Room::where('numid', $randomUserId)->exists());
        $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;
        Room::insertGetId([
            'room_status'          => $request->room_status ,
            'top_room'             => $request->top_room ,
            'numid'                => $randomUserId ,
            'pin'                  => $request->pin ,
            'max_admin'            => $request->max_admin ,
            'room_name'            => $request->name,
            'room_intro'           => $request->intro,
            'room_pass'            => $request->password,
            'room_cover'           => $show_img_name,
            'uid'                  => $request->owner_id,
            'room_background'      => $request->background_id,
        ]);
        return 200;
    }


    public function show(string $id)
    {
        $data = Room::find($id);
        return  $data ;
    }

    public function update(Request $request, string $id)
    {
        $Room = Room::find($id);
        $request->validate([
            'room_status'      => 'required|max:255',
            'top_room'         => 'required|max:255',
            'pin'              => 'required|max:255',
            'max_admin'        => 'required|max:255',
            'name'             => 'required|max:255',
            'intro'            => 'nullable|max:255',
            'password'         => 'nullable|max:255',
            'background_id'    => 'nullable|exists:backgrounds,id',
        ]);
        if( $request->hasFile('show_img'))
        {
            if($Room->room_cover)
            {
                $this->delete_img($Room->room_cover);
            }
            $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;
            $Room->room_cover       = $show_img_name;
        }
        if( $request->background_id )
        {
            $Room->room_background       = $request->background_id;
        }
        $Room->room_status    = $request->room_status ;
        $Room->top_room       = $request->top_room ;
        $Room->pin            = $request->pin;
        $Room->max_admin      = $request->max_admin;
        $Room->room_name      = $request->name;
        $Room->room_intro     = $request->intro;
        $Room->room_pass      = $request->password;
        $Room->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Room = Room::find($id);
        if($Room->room_cover )
        {
            $this->delete_img($Room->room_cover );
        }
        $Room->delete();
        return 200;
    }
}

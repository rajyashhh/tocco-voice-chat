<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Models\Background;
use Illuminate\Http\Request;
use App\Traits\Dashboard\DashBoardTrait;

class AdminBackgroundRoomController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = Background::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort($main_type)
    {

        $items = Background::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Background = Background::find($request->id);
        if($Background)
        {
            if($Background->sort  > $request->new_num)
            {
                $items = Background::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Background::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $Background->sort  =$request->new_num;
            $Background->update();
        }
        return 200;
    }

    public function enable_Background(Request $request, $id , $status)
    {
        $Background = Background::find($id);
        if($Background)
        {
            $Background->enable = $status  == 'true' ? 1 : 0;
            $Background->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'    => 'required|image|mimes:jpeg,png,jpg',
            'enable'      => 'required|max:255',
        ]);
        $last_num = Background::first()->sort;


        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        Background::insert([
            'img'        => $img,
            'enable'      => $request->enable ,
        ]);
        $Background_last =  Background::orderBy('id','desc')->first();
        $Background_last->sort = $last_num+1;
        $Background_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Background::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Background = Background::find($id);
        $request->validate([
            'enable'      => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($Background->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $Background->img   = $img ;
        }
        $Background->enable     = $request->enable ;
        $Background->update();
    }

    public function destroy(string $id)
    {
        $Background = Background::find($id);
        $this->delete_img($Background->img);
        $Background->delete();
        return 200;
    }
}

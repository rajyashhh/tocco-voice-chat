<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Models\Emoji;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminEmojeRoomController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = Emoji::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {

        $items = Emoji::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Emoji = Emoji::find($request->id);
        if($Emoji)
        {
            if($Emoji->sort  > $request->new_num)
            {
                $items = Emoji::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Emoji::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $Emoji->sort  =$request->new_num;
            $Emoji->update();

        }
        return 200;
    }

    public function enable_emoje(Request $request, $id , $status)
    {
        $Emoji = Emoji::find($id);
        if($Emoji)
        {
            $Emoji->enable = $status  == 'true' ? 1 : 0;
            $Emoji->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'    => 'required',
            'name'      => 'required|max:255',
            'enable'      => 'required|max:255',
            't_length'      => 'required|max:255',
        ]);
        $last_num = Emoji::first()->sort;

        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        Emoji::insert([
            'emoji'        => $img,
            'enable'      => $request->enable ,
            'name'      => $request->name ,
            't_length'      => $request->t_length ,
        ]);
        $emoji_last =  Emoji::orderBy('id','desc')->first();
        $emoji_last->sort = $last_num+1;
        $emoji_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = Emoji::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Emoji = Emoji::find($id);
        $request->validate([
            'name'      => 'required|max:255',
            'enable'      => 'required|max:255',
            't_length'      => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($Emoji->emoji);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $Emoji->emoji   = $img ;
        }
        $Emoji->name         = $request->name ;
        $Emoji->enable       = $request->enable ;
        $Emoji->t_length     = $request->t_length ;
        $Emoji->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Emoji = Emoji::find($id);
        if( $Emoji->emoji)
        {
            $this->delete_img($Emoji->emoji);
        }
        $Emoji->delete();
        return 200;
    }
}

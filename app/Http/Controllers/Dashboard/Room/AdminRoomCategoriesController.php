<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminRoomCategoriesController extends Controller
{
    use DashBoardTrait;
    public function index()
    {
        $data = RoomCategory::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {

        $items = RoomCategory::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $RoomCategory = RoomCategory::find($request->id);
        if($RoomCategory)
        {
            if($RoomCategory->sort  > $request->new_num)
            {
                $RoomCategorys = RoomCategory::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();

                foreach ($RoomCategorys as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $RoomCategorys = RoomCategory::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();

                foreach ($RoomCategorys as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $RoomCategory->sort  =$request->new_num;
            $RoomCategory->update();
        }
        return 200;
    }


    public function enable_categories(Request $request, $id , $status)
    {
        $RoomCategory = RoomCategory::find($id);
        if($RoomCategory)
        {
            $RoomCategory->enable = $status  == 'true' ? 1 : 0;
            $RoomCategory->update() ;
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
            'name_ar'      => 'required|max:255',
        ]);
        $last_num = RoomCategory::first()->sort;

        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        RoomCategory::insert([
            'img'        => $img,
            'enable'      => $request->enable ,
            'name'      => $request->name ,
            'name_ar'      => $request->name_ar ,
        ]);
        $Background_last =  RoomCategory::orderBy('id','desc')->first();
        $Background_last->sort = $last_num+1;
        $Background_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = RoomCategory::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $RoomCategory = RoomCategory::find($id);
        $request->validate([
            'name'      => 'required|max:255',
            'enable'      => 'required|max:255',
            'name_ar'      => 'required|max:255',
        ]);

        if( $request->hasFile('img'))
        {
            $this->delete_img($RoomCategory->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $RoomCategory->img   = $img ;
        }
        $RoomCategory->name         = $request->name ;
        $RoomCategory->enable       = $request->enable ;
        $RoomCategory->name_ar     = $request->name_ar ;
        $RoomCategory->update();
    }

    public function destroy(string $id)
    {
        $RoomCategory = RoomCategory::find($id);
        if($RoomCategory->img ) { $this->delete_img($RoomCategory->img);}
        $RoomCategory->delete();
        return 200;
    }
}

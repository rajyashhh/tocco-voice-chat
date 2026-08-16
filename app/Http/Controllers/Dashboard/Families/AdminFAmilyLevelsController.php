<?php

namespace App\Http\Controllers\Dashboard\Families;

use App\Http\Controllers\Controller;
use App\Models\FamilyLevel;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminFAmilyLevelsController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = FamilyLevel::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {

        $items = FamilyLevel::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $FamilyLevel = FamilyLevel::find($request->id);
        if($FamilyLevel)
        {
            if($FamilyLevel->sort  > $request->new_num)
            {
                $items = FamilyLevel::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = FamilyLevel::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $FamilyLevel->sort  =$request->new_num;
            $FamilyLevel->update();

        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'        => 'required',
            'name'       => 'required|max:255',
            'members'    => 'required|max:255',
            'admins'     => 'required|max:255',
            'exp'        => 'required|max:255',
        ]);
        $last_num = FamilyLevel::first()->sort;

        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        FamilyLevel::insert([
            'name'         => $request->name ,
            'img'          => $img,
            'exp'          => $request->exp ,
            'members'      => $request->members ,
            'admins'       => $request->admins ,
        ]);
        $FamilyLevel_last =  FamilyLevel::orderBy('id','desc')->first();
        $FamilyLevel_last->sort = $last_num+1;
        $FamilyLevel_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = FamilyLevel::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $FamilyLevel = FamilyLevel::find($id);
        $request->validate([
            'name'       => 'required|max:255',
            'members'    => 'required|max:255',
            'admins'     => 'required|max:255',
            'exp'        => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($FamilyLevel->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $FamilyLevel->img   = $img ;
        }
        $FamilyLevel->name       = $request->name ;
        $FamilyLevel->members    = $request->members ;
        $FamilyLevel->admins     = $request->admins ;
        $FamilyLevel->exp        = $request->exp ;
        $FamilyLevel->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $FamilyLevel = FamilyLevel::find($id);
        if( $FamilyLevel->img)
        {
            $this->delete_img($FamilyLevel->img);
        }
        $FamilyLevel->delete();
        return 200;
    }
}

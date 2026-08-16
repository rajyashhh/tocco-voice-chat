<?php

namespace App\Http\Controllers\Dashboard\Charing;

use App\Http\Controllers\Controller;
use App\Models\Silver;
use Illuminate\Http\Request;

class AdminCharingSilverController extends Controller
{

    public function index()
    {
        $data = Silver::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {
        $items = Silver::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Silver = Silver::find($request->id);
        if($Silver)
        {
            if($Silver->sort  > $request->new_num)
            {
                $items = Silver::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Silver::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $Silver->sort  =$request->new_num;
            $Silver->update();
        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'coin'      => 'required|max:255',
            'silver'          => 'required|max:255',
         ]);
        $last_num = Silver::first()->sort;

        Silver::insert([
            'coin'      => $request->coin ,
            'silver'      => $request->silver ,
        ]);
        $Silver_last =  Silver::orderBy('id','desc')->first();
        $Silver_last->sort = $last_num+1;
        $Silver_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Silver::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Silver = Silver::find($id);
        $request->validate([
            'coin'      => 'required|max:255',
            'silver'          => 'required|max:255',
        ]);

        $Silver->coin         = $request->coin ;
        $Silver->silver       = $request->silver ;
        $Silver->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Silver = Silver::find($id);
        $Silver->delete();
        return 200;
    }
}

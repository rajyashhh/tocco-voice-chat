<?php

namespace App\Http\Controllers\Dashboard\Charing;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use Illuminate\Http\Request;

class AdminCharingExchangesController extends Controller
{

    public function index()
    {
        $data = Exchange::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {

        $items = Exchange::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Exchange = Exchange::find($request->id);
        if($Exchange)
        {
            if($Exchange->sort  > $request->new_num)
            {
                $items = Exchange::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Exchange::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $Exchange->sort  =$request->new_num;
            $Exchange->update();
        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'diamonds'      => 'required|max:255',
            'value'          => 'required|max:255',
         ]);
        $last_num = Exchange::first()->sort;
        Exchange::insert([
            'diamonds'      => $request->diamonds ,
            'value'      => $request->value ,
        ]);
        $Exchange_last =  Exchange::orderBy('id','desc')->first();
        $Exchange_last->sort = $last_num+1;
        $Exchange_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Exchange::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Exchange = Exchange::find($id);
        $request->validate([
            'diamonds'      => 'required|max:255',
            'value'          => 'required|max:255',
        ]);
        $Exchange->diamonds         = $request->diamonds ;
        $Exchange->value       = $request->value ;
        $Exchange->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Exchange = Exchange::find($id);
        $Exchange->delete();
        return 200;
    }
}

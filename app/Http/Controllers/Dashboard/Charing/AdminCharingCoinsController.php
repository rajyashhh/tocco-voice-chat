<?php

namespace App\Http\Controllers\Dashboard\Charing;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use Illuminate\Http\Request;

class AdminCharingCoinsController extends Controller
{

    public function index()
    {
        $data = Coin::orderBy('sort','asc')->get();
        return $data;
    }

    public function sort()
    {

        $items = Coin::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Coin = Coin::find($request->id);
        if($Coin)
        {
            $Coins = Coin::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
             $Coin->sort  =$request->new_num;
            $Coin->update();
            foreach ($Coins as $item) {
                $item->sort +=1;
                $item->update();
            }
        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'coin'      => 'required|max:255',
            'usd'          => 'required|max:255',
         ]);
        Coin::insert([
            'coin'      => $request->coin ,
            'usd'      => $request->usd ,
        ]);

        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Coin::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Coin = Coin::find($id);
        $request->validate([
            'coin'      => 'required|max:255',
            'usd'          => 'required|max:255',
        ]);

        $Coin->coin         = $request->coin ;
        $Coin->usd          = $request->usd ;
        $Coin->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Coin = Coin::find($id);
        $Coin->delete();
        return 200;
    }
}


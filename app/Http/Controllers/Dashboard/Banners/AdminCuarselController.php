<?php

namespace App\Http\Controllers\Dashboard\Banners;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Banners\AdminBannersResource;
use App\Http\Resources\Dashboard\Banners\AdminCuarselResource;
use App\Models\Banner;
use App\Models\HomeCarousel;
use App\Traits\Dashboard\DashBoardTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminCuarselController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = HomeCarousel::orderBy('sort','asc')->get();
        return AdminCuarselResource::collection($data);
    }

    public function enable_carousel(Request $request, $id , $status)
    {
        $HomeCarousel = HomeCarousel::find($id);
        if($HomeCarousel)
        {
            $HomeCarousel->enable = $status  == 'true' ? 1 : 0;
            $HomeCarousel->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }
    public function sort()
    {

        $items = HomeCarousel::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $HomeCarousel = HomeCarousel::find($request->id);
        if($HomeCarousel)
        {
            if($HomeCarousel->sort  > $request->new_num)
            {
                $items = HomeCarousel::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = HomeCarousel::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $HomeCarousel->sort  =$request->new_num;
            $HomeCarousel->update();

        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'time'          => 'required',
            'enable'        => 'required',
        ]);
        if($request->type == 1)
        {
            $request->validate([
                'user_id'        => 'required|exists:users,id',
            ]);
        }
        if($request->type == 2)
        {
            $request->validate([
                'url'        => 'required',
            ]);
        }

        $last_num = HomeCarousel::orderBy('sort','desc')->first();
        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        $data = new HomeCarousel();
        $data->img    = $img;
        $data->enable = $request->enable;
        $data->type   = $request->type_id;
        $data->form   = $request->form_type_id;
        $data->input  = $request->time;
        $data->sort  = $last_num ? $last_num ->sort + 1 : 1;

        if ((int)$request->type_id == 1) {
            $data->owner_id = $request->user_id;
        } elseif ((int)$request->type_id == 2) {
            $data->url = $request->url;
        }
        $data->save();


        if($request->form === 1)
        {
                $data->duration = Carbon::now()->addHour( $request->input)->timestamp;
        }
        if($request->form === 1)
        {
                $data->duration = Carbon::now()->addDay( $request->input)->timestamp;
        }
        if($request->form === 1)
        {
                $data->duration = Carbon::now()->addMonth( $request->input)->timestamp;
        }

        $data->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = HomeCarousel::find($id);
        return new AdminCuarselResource($data);
    }

    public function update(Request $request, string $id)
    {
        $HomeCarousel = HomeCarousel::find($id);
        $request->validate([
            'time'          => 'required',
            'enable'        => 'required',
        ]);
        if($request->type == 1)
        {
            $request->validate([
                'user_id'        => 'required|exists:users,id',
            ]);
        }
        if($request->type == 2)
        {
            $request->validate([
                'url'        => 'required',
            ]);
        }
        if( $request->hasFile('img'))
        {
            $this->delete_img($HomeCarousel->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $HomeCarousel->img   = $img ;
        }
        if($HomeCarousel->form !== (int)$request->form_type_id || $HomeCarousel->input !== (int)$request->time )
        {
            if($request->form === 1)
            {
                    $HomeCarousel->duration = Carbon::now()->addHour( $request->time)->timestamp;
            }
            if($request->form === 1)
            {
                    $HomeCarousel->duration = Carbon::now()->addDay( $request->time)->timestamp;
            }
            if($request->form === 1)
            {
                    $HomeCarousel->duration = Carbon::now()->addMonth( $request->time)->timestamp;
            }
        }
        $HomeCarousel->enable      = $request->enable ;
        $HomeCarousel->type        = $request->type_id ;
        $HomeCarousel->form        = $request->form_type_id ;
        if((int)$request->type_id === 1)
        {
            $HomeCarousel->owner_id    = $request->user_id ;
            $HomeCarousel->url    = null;
        }
        else if ((int)$request->type_id === 2){
            $HomeCarousel->owner_id    = null;
            $HomeCarousel->url    = $request->url ;
        }
        $HomeCarousel->input        = $request->time ;
        $HomeCarousel->save();
        return 200;
    }

    public function destroy(string $id)
    {
        $HomeCarousel = HomeCarousel::find($id);
        if( $HomeCarousel->img)
        {
            $this->delete_img($HomeCarousel->img);
        }
        $HomeCarousel->delete();
        return 200;
    }
}

<?php

namespace App\Http\Controllers\Dashboard\Banners;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Banners\AdminBannersResource;
use App\Models\Banner;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminBannersController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = Banner::orderBy('sort','asc')->get();
        return AdminBannersResource::collection($data);
    }

    public function enable_banners(Request $request, $id , $status)
    {
        $Banner = Banner::find($id);
        if($Banner)
        {
            $Banner->is_Active = $status  == 'true' ? 1 : 0;
            $Banner->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }
    public function sort()
    {

        $items = Banner::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Banner = Banner::find($request->id);
        if($Banner)
        {
            if($Banner->sort  > $request->new_num)
            {
                $items = Banner::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Banner::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $Banner->sort  =$request->new_num;
            $Banner->update();

        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
           'img'        => 'required',
           'enable'    => 'required|max:255',
           'expire'        => 'required|max:255',
       ]);
       $last_num = Banner::first()->sort;
       $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'files') : null;
       Banner::insert([
           'image_url'          => $img,
           'is_active'    => $request->enable ,
           'expire'       => $request->expire ,
       ]);
       $Banner_last =  Banner::orderBy('id','desc')->first();
       $Banner_last->sort = $last_num+1;
       $Banner_last->save();


       return response()->json([
           'status' => 200 ,
       ]);
    }

    public function show(string $id)
    {
        $data = Banner::find($id);
        return new AdminBannersResource($data);
    }

    public function update(Request $request, string $id)
    {
        $Banner = Banner::find($id);
        $request->validate([
            'expire'       => 'required|max:255',
            'enable'    => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($Banner->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $Banner->image_url   = $img ;
        }
        $Banner->expire      = $request->expire ;
        $Banner->is_Active     = $request->enable ;
        $Banner->save();
        return 200;
    }

    public function destroy(string $id)
    {
        $Banner = Banner::find($id);
        if( $Banner->image_url)
        {
            $this->delete_img($Banner->image_url);
        }
        $Banner->delete();
        return 200;
    }
}

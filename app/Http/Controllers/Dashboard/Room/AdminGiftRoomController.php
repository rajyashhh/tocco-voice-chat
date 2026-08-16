<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Room\AdminGiftsResource;
use Illuminate\Http\Request;
use App\Models\Gift;
use App\Traits\Dashboard\DashBoardTrait;

class AdminGiftRoomController extends Controller
{
    use DashBoardTrait;

    public function index($type)
    {
       $data = Gift::where('type',$type)->orderBy('sort','asc')->get();
       return AdminGiftsResource::collection($data);
    }

    public function enable_gift(Request $request, $id , $status , $type)
    {
        $Gift = Gift::find($id);
        if($Gift)
        {
            if($type == 'music_gift')
            {
                $Gift->music_gift = $status  == 'true' ? 1 : 0;
            }
            else{
                $Gift->enable  = $status  == 'true' ? 1 : 0;
            }
            $Gift->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }

    public function sort()
    {
        $types = [
            ['id' => 1, 'name' => 'normal'],
            ['id' => 2, 'name' => 'hot'],
            ['id' => 3, 'name' => 'country'],
            ['id' => 4, 'name' => 'Moment'],
            ['id' => 5, 'name' => 'Famous gifts'],
            ['id' => 6, 'name' => 'Lucky gifts'],
            ['id' => 7, 'name' => 'Event'],
        ];
        foreach($types as $type)
        {
            $items = Gift::where('type',$type['id'])->orderBy('id','desc')->get();
            $index = 0 ;
            foreach ($items as $item) {
                $index ++;
                $item->sort = $index;
                $item->update();
            }
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Gift = Gift::find($request->id);
        if($Gift)
        {
            if($Gift->sort  > $request->new_num)
            {
                $Gifts = Gift::where('type',$Gift->type)->where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($Gifts as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $Gifts = Gift::where('type',$Gift->type)->where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($Gifts as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $Gift->sort  =$request->new_num;
            $Gift->update();
        }
        return 200;
    }

    public function store(Request $request)
    {
        $request->validate([
            'enable'           => 'required|max:255',
            'music_gift'       => 'required|max:255',
            'name'             => 'required|max:255',
            'price'            => 'required|max:255',
            'type_id'          => 'required|max:255',
            'image_type'       => 'required|max:255',
            'show_img'         => 'required',
            'show_img2'        => 'required',
        ]);
        $last_num = Gift::where('type',$request->type_id)->first()->sort;
        $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;
        $show_img2_name = $request->hasFile('show_img2') ? $this->store_img($request->file('show_img2'), 'images') : null;
        $giftId  = Gift::insertGetId([
            'enable'               => $request->enable ,
            'music_gift'           => $request->music_gift ,
            'name'                 => $request->name,
            'price'                => $request->price,
            'type'                 => $request->type_id,
            'image_type'           => $request->image_type,
            'show_img'             => $show_img_name ,
            'show_img2'            => $show_img2_name,
        ]);
        $Gift_last = Gift::where('type',$request->type_id)->orderBy('id','desc')->first();
        $Gift_last->sort = $last_num+1;
        $Gift_last->save();
        return 200;
    }

    public function show(string $id)
    {
        $data = Gift::find($id);
        return  $data ;
    }

    public function update(Request $request, string $id)
    {
        $Gift = Gift::find($id);
        $request->validate([
            'enable'           => 'required|max:255',
            'music_gift'       => 'required|max:255',
            'name'             => 'required|max:255',
            'price'            => 'required|max:255',
            'type_id'          => 'required|max:255',
            'image_type'       => 'required|max:255',
        ]);
        if( $request->hasFile('show_img'))
        {
            $this->delete_img($Gift->show_img);
            $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;
            $Gift->show_img   = $show_img_name ;
        }
        if( $request->hasFile('show_img2'))
        {
            $this->delete_img($Gift->show_img2);
            $show_img2_name = $request->hasFile('show_img2') ? $this->store_img($request->file('show_img2'), 'images') : null;
            $Gift->show_img2       = $show_img2_name;
        }

        $Gift->enable          = $request->enable ;
        $Gift->music_gift      = $request->music_gift ;
        $Gift->name            = $request->name;
        $Gift->type            = $request->type_id;
        $Gift->image_type      = $request->image_type;
        $Gift->update();

        return 200;
    }

    public function destroy(string $id)
    {
        $ware = Gift::find($id);
        $this->delete_img($ware->show_img);
        $this->delete_img($ware->show_img2);
        $ware->delete();
        return 200;
    }
}

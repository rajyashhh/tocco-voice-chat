<?php

namespace App\Http\Controllers\Dashboard\Wares;

use App\Facades\CustomNotification;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Wares\AdminSpecialHistoryResource;
use App\Http\Resources\Dashboard\Wares\AdminWareResource;
use App\Http\Resources\Dashboard\Wares\AdminWaresResource;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Traits\Dashboard\DashBoardTrait;
use DB;
use Illuminate\Http\Request;
use Modules\SpecialId\Entities\SpecialHistory;

class AdminSpecialIdController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = Ware::where('type',25)->orderBy('sort','asc')->get();
        return AdminWaresResource::collection($data);
    }

    public function sort()
    {

        $items = Ware::where('type',25)->orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $Ware = Ware::find($request->id);
        if($Ware)
        {
            if($Ware->sort  > $request->new_num)
            {
                $items = Ware::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = Ware::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $Ware->sort  =$request->new_num;
            $Ware->update();

        }
        return 200;
    }

    public function enable_special_id(Request $request, $ware_id , $status)
    {
        $Ware = Ware::find($ware_id);
        if($Ware)
        {
            $Ware->enable = $status  == 'true' ? 1 : 0;
            $Ware->update() ;
        }
        return response()->json([
            'status' => 200,
            'Ware' => $Ware,
        ]);
    }

    function send(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'days' => 'required|numeric|min:0',
            'id' => 'required|exists:wares,id',
        ]);
        $user= User::find($request->user_id);
        $ware = Ware::find($request->id);

        $pack = Pack::query()->where('user_id', $user->id)->where('target_id', $ware->id)->first();
        if ($pack) {

            if ($pack->expire == 0) return response()->error('العنصر موجود لدى المستخدم و غير قابل للانتهاء');
            if ($pack->expire > now()->timestamp) {
                if ($ware->expire != 0) {
                    DB::beginTransaction();
                    try {
                        $pack->expire += ($ware->expire * 86400);
                        $pack->save();
                        DB::commit();
                        return 200;
                    } catch (\Exception $exception) {
                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'message' => 'خطا غير متوقع'
                        ],423);
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' =>'العنصر موجود لدى المستخدم'
                    ],423);
                }
            } else {
                $pack->delete();
            }
        }
        DB::beginTransaction();
        try {
            $arr['user_id'] = $user->id;
            $arr['type'] = $ware->type;
            $arr['get_type'] = $ware->get_type;
            $arr['target_id'] = $ware->id;
            $arr['num'] = 1; //$qty;
            $arr['expire'] = $ware->expire ? time() + ($ware->expire * 86400) : 0;
            $arr['is_read'] = 1;
            $arr['receive_type'] = 'admin-send-special-id';
            
            Pack::query()->create($arr);
            DB::commit();
            CustomNotification::wareVip($user, $request->days, $ware->name, $ware->show_img);
            return 200;
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'خطا غير متوقع'
            ],423);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'price'       => 'required|max:255',
            'value'       => 'required|max:255',
            'expire'      => 'required|max:255',
            'enable'      => 'required|max:255',
            'show_img'    => 'required|image|mimes:jpeg,png,jpg',
        ]);
        $last_num = Ware::where('type',25)->orderBy('id','desc')->first();

        $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;

        Ware::insert([
            'type'        => 25,
            'get_type'    => 4,
            'price'      => $request->price,
            'expire'      => $request->expire,
            'value'       => $request->value,
            'enable'      => $request->enable ,
            'show_img'    => $show_img_name ,
        ]);

        $ware_last =  Ware::where('type',25)->orderBy('id','desc')->first();
        $ware_last->sort = $last_num ? $last_num ->sort+1 : 1;
        $ware_last->save();
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = Ware::find($id);
        return new  AdminWareResource($data);
    }

    public function update(Request $request, string $id)
    {
        $ware = Ware::find($id);
        $request->validate([
            'price'       => 'required|max:255',
            'value'       => 'required|max:255',
            'expire'      => 'required|max:255',
            'enable'      => 'required|max:255',
        ]);
        if( $request->hasFile('show_img'))
        {
            $this->delete_img($ware->show_img);
            $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;
            $ware->show_img   = $show_img_name ;
        }


        $ware->value    = $request->value;
        $ware->price    = $request->price;
        $ware->expire    = $request->expire;
        $ware->enable    = $request->enable;
        if($request->level !== 'null')
        {
            $ware->level      = $request->level;
        }
        $ware->update();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function destroy(string $id)
    {
        $ware = Ware::find($id);
        $this->delete_img($ware->show_img);
        $this->delete_img($ware->img2);
        $ware->delete();
        return 200;
    }


    function special_id_history(Request $request)  {
        $query = $request->get('query');
        $check = json_decode($query);
        if( $check->id !== '')
        {
          if($check->type == 'user_id')
            {
              $data = SpecialHistory::where('user_id',$check->id)->orderBy('id','desc')->with('user','ware')->paginate(10);
            }
            else{
                $id =$check->id;
                $data = SpecialHistory::whereHas('ware',function($q) use($id) {
                    $q->where('id',$id);
                })->orderBy('id','desc')->with('user','ware')->paginate(10);;
            }
        }
        else{
            $data = SpecialHistory::orderBy('id','desc')->with('user')->paginate(10);
        }
        return AdminSpecialHistoryResource::collection($data);
    }
}

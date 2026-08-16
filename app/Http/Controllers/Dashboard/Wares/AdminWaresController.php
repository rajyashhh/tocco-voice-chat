<?php

namespace App\Http\Controllers\Dashboard\Wares;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Wares\AdminWareResource;
use App\Http\Resources\Dashboard\Wares\AdminWaresResource;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Selectables\Gifts;
use App\Traits\Dashboard\DashBoardTrait;
use DB;
use Illuminate\Http\Request;

class AdminWaresController extends Controller
{
    use DashBoardTrait;

    public function autocomplete(Request $request)
    {
        $query = $request->get('query');
        $items = Ware::where('name_en', 'like', '%'.$query.'%')->orWhere('name', 'like', '%'.$query.'%')->select('id','name')->limit(10)->get();
        return response()->json($items);
    }

    public function index($main_type,$type)
    {
        $data = Ware::where('get_type',$main_type)->where('type',$type)->orderBy('sort','asc')->get();
        return AdminWaresResource::collection($data);
    }

    public function sort($main_type)
    {
        $types = [
            ['id' => 4, 'name' => 'Avatar Frame'],
            ['id' => 5, 'name' => 'Bubble Frame'],
            ['id' => 6, 'name' => 'Entering Special Effects'],
            ['id' => 8, 'name' => 'Badge'],
            ['id' => 9, 'name' => 'NoKick'],
            ['id' => 10, 'name' => 'Icon'],
            ['id' => 11, 'name' => 'intro animation'],
            ['id' => 12, 'name' => 'wapel'],
            ['id' => 13, 'name' => 'hide country and last login'],
            ['id' => 14, 'name' => 'vip gifts'],
            ['id' => 15, 'name' => 'no pan'],
            ['id' => 16, 'name' => 'hidden room'],
            ['id' => 17, 'name' => 'anonymous man'],
            ['id' => 18, 'name' => 'colored name'],
            ['id' => 19, 'name' => 'profile visitors hide in'],
            ['id' => 20, 'name' => 'hide last active']
        ];
        foreach($types as $type)
        {
            $items = Ware::where('get_type',$main_type)->where('type',$type['id'])->orderBy('id','desc')->get();
            $index = 0 ;
            foreach ($items as $item) {
                $index ++;
                $item->sort = $index;
                $item->save();

            }
        }
        return 200;
    }

    function change_sort(Request $request) {
        $ware = Ware::find($request->id);
        if($ware)
        {
            if($ware->sort  > $request->new_num)
            {
                $wares = Ware::where('get_type',$ware->get_type)->where('type',$ware->type)->where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($wares as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $wares = Ware::where('get_type',$ware->get_type)->where('type',$ware->type)->where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($wares as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }
            $ware->sort  =$request->new_num;
            $ware->update();


        }
        return 200;
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
            $arr['receive_type'] = 'admin-send-wares';

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

    public function enable_wares(Request $request, $ware_id , $status)
    {
        // dd( $ware_id , $status);
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

    public function store(Request $request)
    {
        $request->validate([
            'name_en'     => 'required|max:255',
            'name_ar'     => 'required|max:255',
            'title_en'    => 'nullable|max:255',
            'title_ar'    => 'nullable|max:255',
            'type_id'     => 'required|max:255',
            'get_type_id' => 'required|max:255',
            'price'       => 'required|max:255',
            'level'       => 'nullable|max:255',
            'expire'      => 'required|max:255',
            'color'       => 'nullable|max:255',
            'enable'      => 'required|max:255',
            'show_img'    => 'required|image|mimes:jpeg,png,jpg',
            'img2'        => 'required',
        ]);
        $last_num = Ware::where('get_type',$request->get_type_id)->where('type',$request->type_id)->orderBy('id','desc')->first()->sort;

        $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;
        $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'images') : null;

        Ware::insert([
            'name_en'     => $request->name_en,
            'name'     => $request->name_ar,
            'title_en'    => $request->title_en ?? null,
            'title'    => $request->title_ar ?? null,
            'type'     => $request->type_id,
            'get_type' => $request->get_type_id,
            'price'       => $request->price,
            'level'       => $request->level ?? null,
            'expire'      => $request->expire,
            'color'       => $request->color ?? 'black',
            'enable'      => $request->enable ,
            'show_img'    => $show_img_name ,
            'img2'        => $img2_name,
        ]);
        $ware_last =  Ware::where('get_type',$request->get_type_id)->where('type',$request->type_id)->orderBy('id','desc')->first();
        $ware_last->sort = $last_num+1;
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
            'name_en'     => 'required|max:255',
            'name_ar'     => 'required|max:255',
            'title_en'    => 'nullable|max:255',
            'title_ar'    => 'nullable|max:255',
            'type_id'     => 'required|max:255',
            'get_type_id' => 'required|max:255',
            'price'       => 'required|max:255',
            'level'       => 'nullable|max:255',
            'expire'      => 'required|max:255',
            'color'       => 'nullable|max:255',
            'enable'      => 'required|max:255',
        ]);
        if( $request->hasFile('show_img'))
        {
            $this->delete_img($ware->show_img);
            $show_img_name = $request->hasFile('show_img') ? $this->store_img($request->file('show_img'), 'images') : null;;
            $ware->show_img   = $show_img_name ;
        }
        if( $request->hasFile('img2'))
        {
            $this->delete_img($ware->img2);
            $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'images') : null;
            $ware->img2       = $img2_name;
        }

        $ware->name_en    = $request->name_en;
        $ware->name    = $request->name_ar;
        $ware->title_en   = $request->title_en ?? null;
        $ware->title   = $request->title_ar ?? null;
        $ware->type    = $request->type_id;
        $ware->get_type = $request->get_type_id;
        $ware->price      = $request->price;
        if($request->level !== 'null')
        {
            $ware->level      = $request->level;
        }
        $ware->expire     = $request->expire;
        $ware->color      = $request->color ?? 'black';
        $ware->enable     = $request->enable ;
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
}

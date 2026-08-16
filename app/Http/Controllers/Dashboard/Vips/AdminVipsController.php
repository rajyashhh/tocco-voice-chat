<?php

namespace App\Http\Controllers\Dashboard\Vips;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Vips\AdminVipsResource;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use Modules\Vip\Entities\UserVip;
use App\Models\vip_prev;
use Modules\Vip\Entities\VipPrivilege;
use App\Traits\Dashboard\DashBoardTrait;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Modules\Vip\Helpers\VipCommon;

class AdminVipsController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = OVip::orderBy('sort','asc')->with('privilegs')->get();
        return AdminVipsResource::collection($data);
    }
    public function autocomplete(Request $request)
    {
        $query = $request->get('query');
        $items = OVip::where('name', 'like', '%'.$query.'%')->select('id','name')->limit(10)->get();
        return response()->json($items);
    }

    public function sort()
    {

        $items = OVip::orderBy('id','desc')->get();
        $index = 0 ;
        foreach ($items as $item) {
            $index ++;
            $item->sort = $index;
            $item->save();
        }
        return 200;
    }

    function change_sort(Request $request) {
        $OVip = OVip::find($request->id);
        if($OVip)
        {
            if($OVip->sort  > $request->new_num)
            {
                $items = OVip::where('id','not Like',$request->id)->where('sort','>=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort +=1;
                    $item->update();
                }
            }
            else{
                $items = OVip::where('id','not Like',$request->id)->where('sort','<=',$request->new_num)->orderBy('sort','asc')->get();
                foreach ($items as $item) {
                    $item->sort -=1;
                    $item->update();
                }
            }

             $OVip->sort  =$request->new_num;
            $OVip->update();

        }
        return 200;
    }

    function Send(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'days' => 'required|numeric|min:0',
            'id' => 'required|exists:o_vips,id',
        ]);
        $vip = OVip::find($request->id);
        $user= User::find($request->user_id);
        try {
        $uniqueAttributes = [
            'sender_id' => 0,
            'user_id'   => $user->id,
            'vip_id'    => $vip->id,
            'level'     => $vip->level,
        ];
        $userVip = UserVip::query()->where($uniqueAttributes)->first();
        if (!$userVip) {
   
            VipCommon::createUserVip($vip ,$user ,$request->days , auth()->id() ,'',1,0,0,'admin-vip');

        }
        else {
            $userVip->qty++;
            if($userVip->expire > now()->timestamp){
                $userVip->expire += ($request->days * 86400);
            }else{
                $userVip->expire = now()->timestamp + ($request->days * 86400);
            }
            $userVip->save();
        }
        DB::commit();
        CustomNotification::vips($user, $request->days, $vip->img);
         return 200;
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false
            ],404);
        }
    }

    public function store(Request $request)
    {
         $request->validate([
            'img'        => 'required',
            'name'       => 'required|max:255',
            'level'    => 'required|max:255',
            'price'     => 'required|max:255',
            'expire'        => 'required|max:255',
        ]);
        $last_num = OVip::first()->sort;

        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'files') : null;
        OVip::insert([
            'img'          => $img,
            'name'         => $request->name ,
            'level'          => $request->level ,
            'price'      => $request->price ,
            'expire'       => $request->expire ,
        ]);
        $OVip_last =  OVip::orderBy('id','desc')->first();
        $OVip_last->sort = $last_num+1;
        $OVip_last->save();

        if($request->pivilege_id)
        {
            for($i=0 ; $i < count($request->pivilege_id); $i++)
            {
                $item = VipPrivilege::find($request->pivilege_id[$i]);
                if($item)
                {
                    vip_prev::insert([
                        'o_vip_privilege_id'  => $request->pivilege_id[$i],
                        'o_vip_id'            => $OVip_last->id ,
                    ]);
                }
            }
        }
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = OVip::find($id);
        return new AdminVipsResource($data);
    }

    public function update(Request $request, string $id)
    {
        $OVip = OVip::find($id);
        $request->validate([
            'name'       => 'required|max:255',
            'level'    => 'required|max:255',
            'price'     => 'required|max:255',
            'expire'        => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($OVip->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $OVip->img   = $img ;
        }
        $OVip->name      = $request->name ;
        $OVip->level     = $request->level ;
        $OVip->price     = $request->price ;
        $OVip->expire    = $request->expire ;
        $OVip->save();
        vip_prev::where('o_vip_id',$OVip->id)->delete();
        if($request->pivilege_id)
        {
            for($i=0 ; $i < count($request->pivilege_id); $i++)
            {
                $item = VipPrivilege::find($request->pivilege_id[$i]);
                if($item)
                {
                    vip_prev::insert([
                        'o_vip_privilege_id'  => $request->pivilege_id[$i],
                        'o_vip_id'            => $OVip->id ,
                    ]);
                }
            }
        }
        return 200;
    }

    public function destroy(string $id)
    {
        $OVip = OVip::find($id);
        if( $OVip->img)
        {
            $this->delete_img($OVip->img);
        }
        vip_prev::where('o_vip_id',$OVip)->delete();
        $OVip->delete();
        return 200;
    }
}

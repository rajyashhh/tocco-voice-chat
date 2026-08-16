<?php

namespace App\Http\Controllers\Dashboard\Bans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Bans\BansResource;
use App\Models\Ban;
use Illuminate\Http\Request;
use App\Models\BanType;
use App\Models\Ip;
use App\Models\User;
use Carbon\Carbon;
use DB;

class AdminBansController extends Controller
{

    public function types()
    {
        $data = BanType::all();
        return $data;
    }

    public function index()
    {
        $users = User::whereHas('bans')->get();
        $data = [];
        foreach($users as $user)
        {
            $data [] =[
                'user'=>[
                    'id'                  => $user->id,
                    'uuid'                => $user->uuid,
                    'name'                => $user->name,
                    'img'                 => @$user->profile->avatar,
                ]
            ];
        }
        return $data;
        // return BansResource::collection( $bans) ;
    }


    public function store(Request $request)
    {
        if($request->user_id)
        {
            for($i=0 ; $i < count($request->user_id); $i++)
            {
                $user = User::find($request->user_id[$i]);
                if($user)
                {
                    if($request->type_id)
                    {
                        for($i=0 ; $i < count($request->type_id); $i++)
                        {
                            $data = new Ban();
                            $data->uid = $user->uuid;
                            $data->user_type =0;
                            $data->ban_type_id = $request->type_id[$i];
                            $data->duration = $request->duration;
                            $data->description_ar = $request->reson_ar;
                            $data->description_en = $request->reson_en;
                            $data->type = 'action';
                            $data->save();
                        }
                    }
                    if($request->main_type_id)
                    {
                        for($i=0 ; $i < count($request->main_type_id); $i++)
                        {
                            if($request->main_type_id[$i] === 'device')
                            {
                                $data = new Ban();
                                $data->uid = $user->uuid;
                                $data->user_type =0;
                                $data->device_number = $user->device_token;
                                $data->duration = $request->duration;
                                $data->description_ar = $request->reson_ar;
                                $data->description_en = $request->reson_en;
                                $data->type = 'device';
                                $data->save();
                            }
                            else if ($request->main_type_id[$i] === 'ip')
                            {
                                $ips = Ip::where('uid',$user->id)->get();
                                foreach ($ips as $ip) {
                                    $data = new Ban();
                                    $data->uid = $user->uuid;
                                    $data->user_type =0;
                                    $data->ip = $ip->ip;
                                    $data->duration = $request->duration;
                                    $data->description_ar = $request->reson_ar;
                                    $data->description_en = $request->reson_en;
                                    $data->type = 'ip';
                                    $data->save();
                                }
                            }
                            else {
                                $data = new Ban();
                                $data->uid = $user->uuid;
                                $data->user_type =0;
                                $data->device_number = $user->device_token;
                                $data->duration = $request->duration;
                                $data->description_ar = $request->reson_ar;
                                $data->description_en = $request->reson_en;
                                $data->type = 'noraml';
                                $data->save();
                            }
                        }
                    }
                }
            }
        }
        return 200;
    }


    public function show(string $id)
    {
        $now = Carbon::now();

        $bans = Ban::whereHas('user', function($q) use($id) {
            $q->where('id',$id);
        })
        ->select('uid', 'duration', 'type', 'device_number', 'staff_id', 'description_ar','description_en', 'img', 'ban_type_id')
        ->groupBy(['uid', 'duration', 'type', 'device_number', 'staff_id', 'description_ar', 'description_en','img', 'ban_type_id', 'created_at'])
        ->orderByDesc('created_at')
        ->get();
        return BansResource::collection( $bans) ;
    }

    public function update(Request $request, string $id)
    {
        if($request->ban_type_id)
        {
            $ban = Ban::where('uid',$request->uuid)->where('type',$request->type)->first();
        }
        else{
            $ban = Ban::where('uid',$request->uuid)->where('type',$request->type)->where('ban_type_id',$request->ban_type_id)->first();
        }

        if($ban)
        {
            if($ban->ban_type_id)
            {
                $ban->delete();
            }
            else{
                if($ban->type === 'ip')
                {
                    Ban::where('uid',$ban->uid)->where('type','ip')->delete();
                }
                else{
                    $ban->delete();
                }
            }
        }

        return 200;
    }

    public function destroy(string $id)
    {
        $ban = Ban::find($id);
        if($ban)
        {
            if($ban->ban_type_id)
            {
                $ban->delete();
            }
            else{
                if($ban->type === 'ip')
                {
                    Ban::where('uid',$ban->uid)->where('type','ip')->delete();
                }
                else{
                    $ban->delete();
                }
            }
        }
        return 200;
    }
}

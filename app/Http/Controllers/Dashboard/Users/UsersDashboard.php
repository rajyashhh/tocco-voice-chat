<?php

namespace App\Http\Controllers\Dashboard\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MyPacksResource;
use App\Http\Resources\Dashboard\Bans\BansResource;
use App\Http\Resources\Dashboard\Users\AdminUsersResource;
use App\Http\Resources\Dashboard\Users\SingleUserResource;
use App\Http\Resources\Dashboard\Users\UsersResource;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Events\Entities\RewardWinnerPk;
use Modules\Events\Entities\UserChargeEvent;
use Modules\Events\Entities\WinnerReward;
use App\Http\Resources\Dashboard\Events\AdminEventReportResource;
use App\Http\Resources\Dashboard\GroupChat\GroupChatResource;
use App\Http\Resources\Dashboard\Posts\AdminMomentResource;
use App\Http\Resources\Dashboard\Posts\AdminReelsResource;
use App\Http\Resources\Dashboard\Wares\AdminSpecialHistoryResource;
use App\Models\Ban;
use App\Models\GroupChat;
use App\Models\Profile;
use App\Traits\Dashboard\DashBoardTrait;
use Carbon\Carbon;
use DB;
use Modules\Moment\Entities\Moment;
use Modules\Reals\Entities\Real;
use Modules\SpecialId\Entities\SpecialHistory;

class UsersDashboard extends Controller
{
    use DashBoardTrait;

    public function index(Request $request)
    {
        $query = $request->input('name');
        if($query) {
            $data = User::where(function($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                ->orWhere('phone', 'like', '%'.$query.'%')
                ->orWhere('id', 'like', '%'.$query.'%')
                ->orWhere('uuid', 'like', '%'.$query.'%');
            })
            ->where('type_user',$request->type)
            ->with('profile')
            ->paginate(10);
        } else {
            $data = User::where('id', '!=', $request->user()->id)
            ->where('type_user',$request->type)->with('profile')
            ->paginate(10);
        }
        return AdminUsersResource::collection($data);
    }

    public function autocomplete(Request $request)
    {
        $query = $request->get('query');
        $items = User::where('name', 'like', '%'.$query.'%')->select('id','name')->limit(10)->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'uuid' => 'required|unique:users,uuid',
            'name' => 'required',
            'bio' => 'required',
            'phone' => 'required|unique:users,phone',
            'type_id' => 'required',
            'can_play' => 'required',
            'can_charge' => 'required',
            'coins' => 'required',
            'diamonds' => 'required',
            'salary' => 'required',
            'sender_level' => 'required',
            'receiver_level' => 'required',
            'country_id' => 'required|exists:countries,id',
        ]);
        $user = new User();

        $user->uuid = $request->uuid;
        $user->name = $request->name;
        $user->bio = $request->bio;
        $user->phone = $request->phone;
        $user->type_user = $request->type_id;
        $user->can_play =  (int)$request->can_play   === 1 ? 2 : 3;
        $user->charge_status = (int) $request->can_charge   === 1 ? 1 : 0;
        $user->coins = $request->coins;
        $user->country_id = $request->country_id;
        $user->total_diamond_received = $request->diamonds;
        $user->salary = $request->salary;
        $user->total_received_level = $request->receiver_level;
        $user->total_sender_level = $request->sender_level;
        $user->save();
        $profile = Profile::where('user_id',$user->id)->first();
        if( $request->hasFile('img'))
        {
            $this->delete_img($profile->avatar);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $profile->avatar   = $img ;
            $profile->save();
        }
        return 200;
    }

    public function show(string $id)
    {
        $user = User::find($id);
        return new SingleUserResource($user);
    }

    public function user_targets(Request $request , $id)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $user = User::find($id);
        $targets =$user->targets()->orderBy('created_at', 'desc')->whereBetween('created_at',[ $start_date,  $end_date])->get()->map(function ($target) {
            $target = $target->only([
                    'id',
                    'add_month',
                    'add_year',
                    'target_usd',
                    'target_hours',
                    'target_days',
                    'target_agency_share',
                    'user_diamonds',
                    'user_hours',
                    'user_days',
                    'user_obtain',
                    'agency_obtain',
                    'updated_at'
            ]);
            return $target;
        });
        return $targets;
    }

    public function user_bans(string $id)
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

    public function user_moments(Request $request, $id)
    {
        $data = Moment::where('user_id',$id)->orderBy('id','desc')->with('comments','likes')->paginate(10);
        return AdminMomentResource::collection($data);
    }

    public function user_reels(Request $request, $id)
    {
        $data = Real::where('user_id',$id)->orderBy('id','desc')->with('comments','likes')->paginate(10);
        return  AdminReelsResource::collection($data);
    }

    public function user_group_chats(Request $request , $id)
    {
        $data = GroupChat::where('user_id',$id)->orderBy('id','desc')->with('user')->paginate(10);
        return  GroupChatResource::collection($data);
    }

    public function event_report( $id ,$type)
    {
        $data = null;
        if($type === 'pk')
        {
            $data = RewardWinnerPk::whereHas('winner',function($q) use ($id) {
                $q->where('id',$id);
            })->with('winner','reward')->get();
        }
        else if($type === 'weekly_star')
        {
            $data = WinnerReward::where('type','weekly_star')
        ->whereHas('winnerRow',function($q) use ($id) {
            $q->where('user_id',$id);
        })->with('winner','reward')->get();
        }
        else if($type === 'event_period')
        {
            $data = WinnerReward::where('type','event_period')
            ->whereHas('winnerRow',function($q) use ($id) {
                $q->where('user_id',$id);
            })->with('winner','reward')->get();
        }
        return AdminEventReportResource::collection( $data);

    }

    public function user_pack( $id)
    {
        $user = User::find($id);
        $types = [
            ['id' => 4 , 'name'=>'wable'],
            ['id' => 5 , 'name'=>'frame'],
            ['id' => 6 , 'name'=>'entro'],
        ];
        // $this->unlock_dress($id);
        $array = [];
        foreach ($types as $item) {
            $type =$item['id'];
            $where['a.user_id'] = $id;
            $where['a.type']    = $type;

            $data = DB::table('packs', 'a')->join('wares as b', 'a.target_id', '=', 'b.id')
            ->where($where)
            ->selectRaw("a.*,b.name,b.show_img,b.title,b.color")
            ->get();
            if (in_array($type, [4, 5, 6, 7])) {
                $user_dress_after_i_changed = [
                    4 => 1,
                    5 => 2,
                    6 => 3,
                    7 => 4
                ];
                $dress_id  = DB::table('users')->where(['id' => $id])->value("dress_" . $user_dress_after_i_changed[$type]);
            }
            foreach ($data as $k => &$v) {
                $v->is_dress = 0;
                $v->is_used  = $v->use_num == 1 ? 1 : 0;
                if (in_array($type, [4, 5, 6, 7])) {
                    $v->title    = empty($v->expire) ? "permanent" : date('Y-m-d H:i:s', $v->expire) . " expire";
                    $v->is_dress = $dress_id == $v->target_id ? 1 : 0;
                    $v->color    = $v->color ?: '';
                } elseif ($type == 2) {
                    $v->title = "have" . $v->num . "value" . $v->num * $v->price . "diamond";
                    $v->color = '';
                } else {
                    $v->title = "have" . $v->num . "indivual " . $v->title;
                    $v->color = $v->color ?: '';
                }

                if ($v->expire != 0) {
                    $v->expire = date("Y-m-d H:i:s", $v->expire);
                }

                $types       = [
                    '1' => 'gem',
                    '2' => 'gifts',
                    '3' => 'coupons',
                    '4' => 'avatar frames',
                    '5' => 'bubble boxes',
                    '6' => 'entry effects',
                    '7' => 'mic on the aperture',
                    '8' => 'badges',
                    '25' => 'special id'
                ];
                $get_types   = [
                    '1' => 'vip level automatic acquisition',
                    '2' => 'activities',
                    '3' => 'treasure box',
                    '4' => 'purchase',
                    '5' => 'background addition',
                    '6' => 'limited time purchase'
                ];
                $v->type     = __($types[$v->type]);
                $v->get_type = __($get_types[$v->get_type]);

                //status changed to read
                if ($v->is_read == 1) {
                    DB::table('packs')->where(['id' => $v->id])->update(['is_read' => 0]);
                }
            }
            $array[$item['name']]  = MyPacksResource::collection($data);
        }
        $SpecialHistory = SpecialHistory::where('user_id',$id)->orderBy('id','desc')->with('user','ware')->paginate(10);
        $array['special_id_history']  = AdminSpecialHistoryResource::collection($SpecialHistory);
        $array['medals']  = $user->medals;
        $array['Vips']  =  $user->UserVip;
        return $array;
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        $profile = Profile::where('user_id',$id)->first();

        $request->validate([
            'uuid' => 'required|unique:users,uuid,'.$id,
            'name' => 'required',
            'bio' => 'required',
            'phone' => 'required|unique:users,phone,'.$id,
            'type_id' => 'required',
            'can_play' => 'required',
            'can_charge' => 'required',
            'coins' => 'required',
            'diamonds' => 'required',
            'salary' => 'required',
            'sender_level' => 'required',
            'receiver_level' => 'required',
            'country_id' => 'required|exists:countries,id',
        ]);

        $user->uuid = $request->uuid;
        $user->name = $request->name;
        $user->bio = $request->bio;
        $user->phone = $request->phone;
        $user->type_user = $request->type_id;
        $user->can_play =  (int)$request->can_play   === 1 ? 2 : 3;
        $user->charge_status = (int) $request->can_charge   === 1 ? 1 : 0;
        $user->coins = $request->coins;
        $user->country_id = $request->country_id;
        $user->total_diamond_received = $request->diamonds;
        $user->salary = $request->salary;
        $user->total_received_level = $request->receiver_level;
        $user->total_sender_level = $request->sender_level;
        $user->save();

        if( $request->hasFile('img'))
        {
            $this->delete_img($profile->avatar);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $profile->avatar   = $img ;
            $profile->save();
        }

        return 200;
    }

    public function destroy(string $id)
    {
        //
    }

    public function can_play(Request $request, $user_id , $status)
    {
        $user = User::find($user_id);
        if($user)
        {
            $user->can_play = $status  == 'true' ? 2 : 3;
            $user->update() ;
        }
        return response()->json([
            'status' => 200,
            'user' => $user,
            'status' =>  $status ,
        ]);
    }

    public function can_charge(Request $request,$user_id , $status)
    {

        $user = User::find($user_id);
        if($user)
        {
            $user->charge_status =  $status == 'true' ? 1 : 0;
            $user->update() ;
        }
        return response()->json([
            'status' => 200,
            'user' => $user,
            'status' =>  $status ,

        ]);
    }
}

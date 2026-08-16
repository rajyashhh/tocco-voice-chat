<?php

namespace Modules\AgencyApp\Transformers;

use App\Models\AgencyJoinRequest;
use App\Models\Follow;
use App\Models\LiveTime;
use App\Models\ProfileVisitor;
use App\Models\UserSallary;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class HostDailyReportResource extends JsonResource
{
   
    public function toArray($request)
    {
        $join_date = AgencyJoinRequest::where(['user_id' => $this->id, 'agency_id'=>$this->agency_id])->first()?->updated_at;

        $days       =  LiveTime::query()
                ->where('uid', $this->id)
                ->whereDate('created_at','<=', $request->date)
                ->groupBy('uid')
                ->selectRaw('uid, SUM(hours) AS hnum, COUNT(DISTINCT DATE(created_at)) as days')
                ->havingRaw('SUM(hours) >= 1')
                ->get()
                ->sum('days') ?? 0;
                
        $hours       = LiveTime::query()->where('uid', $this->id)
                ->whereDate('created_at', $request->date)
                ->sum('hours');
        $visitors   = ProfileVisitor::query()->where('user_id',$this->id)
                ->whereDate('created_at', $request->date)
                ->count();
        $follows    = Follow::query()->where(fn($q)=>$q->where("followed_user_id",$this->id)
                ->orWhere(fn($q2)=>$q2->where("user_id",$this->id)->where("status",1)))
                ->whereDate('created_at', $request->date)
                ->count();
        $friends    =   Follow::query()->where(fn($q)=>$q->where("followed_user_id",$this->id)->orwhere("user_id",$this->id))
                ->where("status",1)
                ->whereDate('created_at', $request->date)
                ->count();

        return [
            'date'       => $request->date,
            'agency_id'  => $this->agency_id,
            'uuid'       => $this->uuid,
            'join_date'  => $join_date,
            'name'       => $this->name,
            'days'       => $days,
            'hours'      => $hours,
            'visitors'   => $visitors,
            'follows'    => $follows,
            'friends'    => $friends,
        ];
    }
}

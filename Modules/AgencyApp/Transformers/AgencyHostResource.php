<?php

namespace Modules\AgencyApp\Transformers;

use App\Models\AgencyJoinRequest;
use App\Models\Follow;
use App\Models\ProfileVisitor;
use App\Models\UserSallary;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AgencyHostResource extends JsonResource
{
   
    public function toArray($request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $previousMonth = $today->subMonth();
        $user_sallary = UserSallary::where(['user_id' => $this->id, 'month'=> $previousMonth->format('m'), 'year'=> $previousMonth->format('Y')])->first();
        // $join_date = AgencyJoinRequest::where(['user_id' => $this->id, 'agency_id'=>$user->agency_id])->first()?->updated_at;

        $last_month_di = 0;
        if ($user_sallary) {
            $stringWithoutSpaces = str_replace(' ', '', $user_sallary->diamond);
            $parts = explode('/', $stringWithoutSpaces);
            $last_month_di = intval($parts[0]);
        }
        $friends    =   Follow::query()->where(fn($q)=>$q->where("followed_user_id",$this->id)->orWhere("user_id",$this->id))
                        ->where("status",1)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count();
        $visitors   = ProfileVisitor::query()->where('user_id',$this->id)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count();
        $follows    = Follow::query()->where(fn($q)=>$q->where('user_id',$this->id)
                        ->orWhere(fn($q2)=>$q2->where('user_id',$this->id)->where("status",1)))
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count();
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'image' => $this->profile?->avatar,
            'monthly_diamond' => $this->monthly_diamond_received,
            'last_month_diamond' => $last_month_di,
            'date_of_join' => $this->join_agency_date,
            'visitors' => $visitors,
            'followers' => $follows,
            'friends' => $friends,
            'last_active' =>  Carbon::parse($this->online_time)->toDateTimeString(),
        ];
    }
}

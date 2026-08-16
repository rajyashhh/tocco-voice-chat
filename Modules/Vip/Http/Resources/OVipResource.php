<?php

namespace  Modules\Vip\Http\Resources;

use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Resources\Json\JsonResource;

class OVipResource extends JsonResource
{

    public function toArray($request)
    {
        $userVip = UserVip::where("user_id", auth()->user()->id)
            ->where("vip_id", $this->id)
            ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
            ->get();

        $activePrivilegeIds = $this->privilegs->pluck('id')->toArray();

        return [
            'id' => $this->id,
            'level' => $this->level,
            "sort" => $this->sort,
            "name" => $this->name,
            "img" => $this->img,
            "price" => $this->price,
            "expire" => $this->expire,
            "exp" => $this->exp,
            'user_vip' => UserVipResource::collection($userVip),
            'privilegs' => VipPrivilegeResource::collection(
                $request->vipPrivileges->map(function ($p) use ($activePrivilegeIds) {
                    $priv = clone  $p;
                    $priv->item = $this->wares->where('type', $priv->type)->first();
                    $priv->level = $this->level;
                    $priv->active = in_array($priv->id, $activePrivilegeIds);
                    return $priv;
                })->sortByDesc('active')
            ),
        ];
    }
}

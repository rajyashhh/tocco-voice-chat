<?php

namespace Modules\Vip\Http\Resources;

use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class OVipNewResource extends JsonResource
{

    public function toArray($request)
    {
        $oVip = $this->OVip;

        $activePrivilegeIds = [];
        $wares = collect();
        $vipPrivileges = $request->vipPrivileges ?? collect();

        if ($oVip) {
            $activePrivilegeIds = $oVip->privilegs?->pluck('id')?->toArray() ?? [];
            $wares = $oVip->wares ?? collect();
        }
        $diff = null;
        if ($this->expire) {
            $expireDate = Carbon::createFromTimestamp($this->expire);
            $diff = Carbon::now()->diff($expireDate);
        }

        return [
            "target_id" => $this->id,
            "is_buyed" => true,
            "is_used" => $this->is_used == 1,
            "using" => $this->using == 1,
            'expire' => $this->expire != 0 ? ($this->expire == null ? 0 : date("Y-m-d H:i:s", $this->expire)) : 0,
            'remaining_time' => $this->expire && $diff
                    ? sprintf('%dd %dh %dm', $diff->days, $diff->h, $diff->i)
                    : sprintf('%dd', $this->days),

            'vip' => [
                'id' => $oVip?->id ?? '',
                'level' => $oVip?->level ?? '',
                'sort' => $oVip?->sort ?? '',
                'name' => $oVip?->name ?? '',
                'img' => $oVip?->img ?? '',
                'price' => $oVip?->price ?? '',
                'expire' => $oVip?->expire ?? '',
                'exp' => $oVip?->exp ?? '',

                'privilegs' => VipPrivilegeResource::collection(
                    $vipPrivileges->map(function ($p) use ($wares, $activePrivilegeIds, $oVip) {
                        $priv = clone $p;
                        $priv->item = $wares->where('type', $priv->type)->first();
                        $priv->level = $oVip?->level;
                        $priv->active = in_array($priv->id, $activePrivilegeIds);
                        return $priv;
                    })->sortByDesc('active')
                ),
            ]
        ];
    }
}

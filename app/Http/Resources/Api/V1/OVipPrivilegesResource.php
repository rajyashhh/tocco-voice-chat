<?php

namespace App\Http\Resources\Api\V1;
use Illuminate\Http\Resources\Json\JsonResource;

class OVipPrivilegesResource extends JsonResource
{

    public function toArray($request)
    {

        $activePrivilegeIds = $this->privilegs->pluck('id')->toArray();

        return [
            'id' => $this->id,
            'level' => $this->level,
            "sort"=> $this->sort,
            "name"=> $this->name,
            "img"=> $this->img,
            "price"=> $this->price,
            "expire"=> $this->expire,
            "exp"=> $this->exp,
            'privileges' => PrivilegeResource::collection(
                $request->vipPrivileges->map(function ($p) use ($activePrivilegeIds) {
                    $priv = clone  $p;
                    $priv->active = in_array($priv->id, $activePrivilegeIds);
                    return $priv;
                })->sortByDesc('active')
            ),
        ];
    }
}

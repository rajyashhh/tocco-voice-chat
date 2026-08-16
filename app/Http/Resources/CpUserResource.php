<?php

namespace App\Http\Resources;

use Modules\CP\Entities\CpLevel;
use Illuminate\Http\Resources\Json\JsonResource;

class CpUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $loginUserId = request('id');
        if ($this->user_one_id == $loginUserId) {
            $user = $this->toUser;
        } else {
            $user = $this->fromUser;
        }

        $dress_1_data = $this->getUserDress($user, 4, $user->dress_1, 'img2');
        $dress_1_fallback = $this->getUserDress($user, 4, $user->dress_1, 'img1');
        $frame = $dress_1_data ?: $dress_1_fallback;

        $nextLevel = CpLevel::where("cp_relation_id", $this->cp_relation_id)
            ->where("id", ">", $this->level_id)
            ->where("exp", ">", 0)
            ->orderBy('id')
            ->first();
        $ratio = 0;
        if ($nextLevel) {
            $nextLevelPercentage = $nextLevel->level;
            $ratio = $this->di / $nextLevel->exp;
        } else {
            $nextLevelPercentage = 0;
        }
        return [
            'id'        => $this->id,
            'level'     => $this->level_id,
            'next_level'     => $nextLevelPercentage,
            'diamonds'        => $this->di ?? 0,
            'ratio' => $ratio,
            "user"      => [
                "id"        => $user?->id ?? 0,
                "uid"       => $user?->uuid ?? '',
                "name"      => $user?->name ?? '',
                "image"     => $user?->avatar ?? '',
                "gender"    => (string)($user?->gender == 'male' ? 1 : 0),
                'frame' => $frame,
            ],
            "relation" => $this->relation,
            'frame' => $frame,
        ];
    }

    public function getUserDress($user, $type, $dress, $item = 'img1')
    {
        $pack = $user->packs
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();

        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }
}

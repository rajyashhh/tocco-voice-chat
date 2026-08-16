<?php

namespace Modules\CP\Transformers;

use App\Helpers\Common;
use App\Helpers\UserPackHelper;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CP\Entities\Cp;
use Modules\CP\Entities\CpLevel;

class CpListResource extends JsonResource
{
    public function toArray($request)
    {
        $loginUserId = request('user_id') ?? Auth::id();
        if ($this->user_one_id == $loginUserId) {
            $user = $this->toUser;
        } else {
            $user = $this->fromUser;
        }

        $frame = UserPackHelper::getFrameImage($user);

        $currentLevel = CpLevel::find($this->level_id);
        $nextLevel = CpLevel::where("cp_relation_id",  $this?->cp_relation_id)->where("id", ">", $this->level_id)->orderBy('id')->first();
        $currentExp = is_object($currentLevel) ? $currentLevel->exp : 0;

        $ratio = 0;

        if (
            ($currentLevel || $currentLevel === 0) &&
            $nextLevel &&
            $nextLevel->exp > $currentExp
        ) {

            $nextExp = $nextLevel->exp;

            $progress = max(0, $this->di - $currentExp);
            $required = $nextExp - $currentExp;

            $ratio = round(min(($progress / $required) * 100, 100), 2);
        } else {
            $ratio = 100;
        }

        return [
            'id'        => $this->id,
            'level'     => $currentLevel?->level ?? 0,
            'next_level'     =>  $nextLevel?->level ?? 0,
            'di'        => $this->di,
            'ratio' => $ratio,
            "user"      => [
                "id"        => $user?->id,
                "uid"       => $user?->uuid,
                "name"      => $user?->name,
                "image"     => $user?->profile?->avatar,
                "gender"    => (string)($user?->gender == 'male' ? 1 : 0),
                'frame' => $frame,
            ],
            "relation" => $this->relation,
            'frame' => $frame,

        ];
    }
    public function getUserDress($user, $type, $dress, $item = 'show_img')
    {
        $pack = $user->packs
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();

        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }
}

<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\GiftLog;

use Illuminate\Support\Facades\Cache;
use Modules\CP\Entities\Cp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CP\Transformers\CpDataResource;
use Modules\CP\Transformers\CpListResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DataUserResource extends JsonResource
{

    public function toArray($request)
    {
        $family = $this->family;
        $f = null;

        if ($family) {

            $f = [
                'id' => $family->id,
                'owner_id' => $family->user_id,
                'family_name' => $family->name ?? '',
                'max_num' => $family->num ?? 0,
                'img' => $family->image,
                'num_of_members' => $family->members_count,
                'level' => $family->level,
            ];
        }

        $achievement_images = [];
        if ($this->medals) {
            foreach ($this->medals as $medal) {
                if ($medal->achievementLevel) {
                    $achievement_images[] = $medal->achievementLevel->valid_image;

                    // Stop after collecting 3 images
                    if (count($achievement_images) === 3) {
                        break;
                    }
                }
            }
        }
        $userId = $this->id;
        $gifts = GiftLog::select('giftId', DB::raw('SUM(giftPrice) as t'))
            ->where('receiver_id', $this->id)
            ->whereHas('gift')
            ->where('giftId', '!=', 0)
            ->groupBy('giftId')
            ->orderByDesc('t')
            ->with('gift')->take(3)
            ->get();

        $data = Cp::with([
            'relation:id,title,type',
            'toUser.profile',
            'fromUser.profile',
            'toUser.packs',
            'fromUser.packs',
        ])
            ->whereHas("cpRelation", function ($q) {
                $q->where('type', "!=", 'solution');
            })
            ->where(function ($query) use ($userId) {
                $query->where('user_one_id', $userId)
                    ->orWhere('user_two_id', $userId);
            })
            ->whereIn('status', [1, 4])
            ->orderByDesc('di')->get();

        $mainCp = $data->firstWhere('relation.type', 'lovely');

        return [
            'family_data'          => @$f,
            'achievement_images' => $achievement_images,
            'gifts' =>  GiftLogResource::collection($gifts),
            'cp' => $mainCp ? new CpDataResource($mainCp) : null,
            'is_followed' => $this->isFollowedBy(auth()->id()),
            'colored_name' => $this->color_image,
            'image_color'          => @$this->color_image,
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'level' => [
                'receiver_img' => $this->receiverLevel?->img ?? '',
                'exp_receiver' => $this->receiverLevel?->exp ?? 0,
                'sender_img'   => $this->senderLevel?->img ?? '',
            ],
        ];
    }
}

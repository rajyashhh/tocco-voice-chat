<?php

namespace App\helper;

use App\Http\Resources\Api\V1\NewAgencyRankingResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Repositories\RankingRepository;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use App\Http\Resources\Api\V1\MangerTypeResource;

class RankingHelper
{

    public function __construct(
        protected RankingRepository $rankingRepo
    ) {}


    public static function transformUserData($data, int $class, string $key, string $relation)
    {
        $data = $data->values()->map(function ($item, $idx) use ($data) {
            $item->exp_diff = $idx === 0 ? 0 : $data[$idx - 1]->total_gifts - $item->total_gifts + 1;
            return $item;
        });

        return $data->map(function ($v) use ($class) {
            $achievement_images = [];
            $user = $v->ranker;

            if (!$user) {
                return null;
            }

            if ($user->medals) {
                foreach ($user->medals as $medal) {
                    $achievement_images[] = [
                        'image'      => @$medal->custom_image ?? @$medal->achievementLevel->valid_image,
                        'title'      => @$medal->achievementLevel?->achievement?->name ?? 'Reward',
                        'created_at' => @$medal->created_at,
                    ];
                }
            }

            $v->user_id = $user->id;
            $v->color_name = UserPackHelper::getColorName($user);

            $v->exp = numToString(ceil((float)$v->total_gifts));
            $v->exp_int = ceil($v->total_gifts);

            $v->remaining = numToString(ceil($v->exp_diff));
            $v->remaining_int = ceil($v->exp_diff);

            $v->name = $class == 3 ? (@$user->ownerRoom?->room_name ?? '') : $user->name;
            $v->avatar = $class == 3 ? (@$user->ownerRoom?->room_cover ?? '') : $user->profile?->avatar;

            $v->frame = UserPackHelper::getFrameImage($user);
            $v->frame_id = UserPackHelper::getFrameId($user);

            $v->vip_level = $user->UserVip->level ?? 0;
            $v->vip_level_img = UserPackHelper::getVipIcon($user);

            // $v->sender_level = $user->total_sender_level ?? 0;
            // $v->reciver_level = $user->total_received_level ?? 0;
            // $v->sender_level_img = UserLevelHelper::getSenderImage($user);
            // $v->reciver_level_img = UserLevelHelper::getReceiverImage($user);

            $v->country = @$user->country;
            $v->age = @$user->profile?->age ?? 0;

            $v->type_user = intval(@$user->type_user) ?: 0;
            // $v->manger_type = !$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);

            // $v->achievement_images = $achievement_images;
            // $v->room = $class == 3 ? self::roomData(@$user->ownerRoom) : null;

            return $v;
        })->reject(fn($v) => $v == null);
    }

    protected function getClassKeywordsAndRelation(int $class): array
    {
        return match ($class) {
            1 => ['user_id', 'user'],
            2 => ['agency_id', 'agency'],
            3 => ['room_id', 'roomOwner'],
            default => ['user_id', 'user']
        };
    }

    protected function roomData($room)
    {
        if (!$room) return null;
        return [
            'id'   => $room->id,
            'name' => $room->room_name,
            'cover'=> $room->room_cover,
        ];
    }

   private static function getUserSortValue($data, $userId)
    {
        foreach ($data as $idx => $item) {
            if ($item->ranker?->id === $userId) {
                return $idx + 1;
            }
        }
        return null;
    }
    public static function validateParams(int $class, int $type): ?JsonResponse
    {
        $validClasses = [1, 2, 3, 4, 5, 6];
        $validTypes   = [0, 1, 2, 3, 4];

        if (!in_array($class, $validClasses) || !in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter error',
                'data'    => null,
            ], 422);
        }

        return null;
    }

    public static function getLimit(bool $isHome): int
    {
        return $isHome ? 3 : 10;
    }

    public static function transformData(int $class, $data)
    {
        if ($class === 5) {
            return NewAgencyRankingResource::collection($data);
        }
        return $data;
    }



    
}

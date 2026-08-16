<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Api\V1\NewUserRankingResource;
use Illuminate\Http\Resources\Json\ResourceCollection;


class UsersRankingCollection extends ResourceCollection
{
    public $collects = NewUsersRankingResource::class;

    protected $user;
    protected $key;
    protected $userExp;

    public function __construct($resource, $user, $key = 'user_id', $userExp = null)
    {
        parent::__construct($resource);
        $this->user = $user;
        $this->key = $key;
        $this->userExp = $userExp;
    }

    public function toArray($request)
    {
        // Convert to plain array
        $data = NewUsersRankingResource::collection($this->collection)->toArray($request);

        // Remove null values (important!)
        $data = array_values(array_filter($data));

        // First page logic
        if ($this->currentPage() == 1) {
            $count = count($data);

            $kong = [
                'user_id' => 0,
                'uuid' => '',
                'exp' => '0',
                'exp_int' => 0,
                'remaining' => '0',
                'remaining_int' => 0,
                'name' => '',
                'avatar' => '',
                'frame' => '',
                'frame_id' => 0,
                'vip_level' => 0,
                'sender_level' => 0,
                'reciver_level' => 0,
                'vip_level_img' => '',
                'sender_level_img' => '',
                'reciver_level_img' => '',
                'age' => 0,
                'type_user' => 0,
                'manger_type' => null,
                'achievement_images' => [],
                'color_name' => '',
            ];

            // Guarantee 3 records
            $data[0] = $data[0] ?? $kong;
            $data[1] = $data[1] ?? $kong;
            $data[2] = $data[2] ?? $kong;

            return [
                'user'  => $this->formatUser(),
                'top'   => $count < 4 ? $data : array_slice($data, 0, 3),
                'other' => $count < 4 ? [] : array_slice($data, 3),
            ];
        }

        // Other pages only show "other"
        return [
            'other' => $data,
        ];
    }



    protected function formatUser()
    {
        $user = $this->user;
        $achievement_images = [];
        if ($this->user->medals) {
            foreach ($this->user->medals as $medal) {
                if ($medal->achievementLevel) {
                    $achievementData = [
                        'image'      => @$medal->achievementLevel->valid_image,
                        'title'      => @$medal->achievementLevel?->achievement?->name ?? '',
                        'created_at' => @$medal->created_at,
                    ];
                    $achievement_images[] = $achievementData;
                }
            }
        }
        $hasColor = Common::hasInPackV2($user->packs, 18, true);

        $color_name = (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVipV2($user->id, 18, 'color') : null);

        return [
            'user_id'          => $user->id,
            'uuid'             => $user->uuid,
            'exp'              => ($this->userExp?->total_gifts ?? '0'),
            'vip_level'        => $user->UserVip?->level ?? 0,
            'sender_level'     => $user->total_sender_level ?? 0,
            'reciver_level'    => $user->total_received_level ?? 0,
            'vip_level_img'    => UserPackHelper::getVipIcon($user),
            'sender_level_img' => UserLevelHelper::getSenderImage($user),
            'reciver_level_img' => UserLevelHelper::getReceiverImage($user),
            'type_user'        => intval($user->type_user) ?: 0,
            'country'          => $user->country,
            'manger_type'      => !$user->mangerType ? null : new MangerTypeResource($user->mangerType),
            'age'              => $user->profile?->age ?? '',
            'color_name'       =>  $color_name,
            'achievement_images' => $achievement_images,
        ];
    }
}

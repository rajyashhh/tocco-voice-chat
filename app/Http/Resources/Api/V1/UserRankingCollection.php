<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Api\V1\NewUserRankingResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserRankingCollection extends ResourceCollection
{
    public $collects = NewUserRankingResource::class;

    protected $user;
    protected $key;
    protected $userExp;

    // expose the paginator so the controller can pass it to apiResponse
    protected ?LengthAwarePaginator $otherPaginator = null;

    public function __construct($resource, $user, $key, $userExp = null)
    {
        parent::__construct($resource);
        $this->user    = $user;
        $this->key     = $key;
        $this->userExp = $userExp;
    }

    public function getOtherPaginator(): ?LengthAwarePaginator
    {
        return $this->otherPaginator;
    }

    public function toArray($request): array
{
    $data   = $this->collection->values();
    $top    = $data->take(3)->values();   // may return < 3 items
    $others = $data->slice(3)->values();

    // 🔹 ensure top always has 3 items
    $kong = [
        'user_id'            => 0,
        'uuid'               => '',
        'exp'                => '0',
        'exp_int'            => 0,
        'remaining'          => '0',
        'remaining_int'      => 0,
        'name'               => '',
        'avatar'             => '',
        'frame'              => '',
        'frame_id'           => 0,
        'sender_img'         => '',
        'reseverimg'         => '',
        'vip_level'          => 0,
        'sender_level'       => 0,
        'reciver_level'      => 0,
        'vip_level_img'      => '',
        'sender_level_img'   => '',
        'reciver_level_img'  => '',
        'age'                => 0,
        'type_user'          => 0,
        'manger_type'        => null,
        'achievement_images' => [],
        'color_name'         => '',
    ];

    // fill missing slots
    for ($i = $top->count(); $i < 3; $i++) {
        $top->push($kong);
    }

    // paginate "other"
    $perPage     = (int) $request->get('per_page', 10);
    $currentPage = LengthAwarePaginator::resolveCurrentPage('page');

    $this->otherPaginator = new LengthAwarePaginator(
        $others,
        $others->count(),
        $perPage,
        $currentPage,
        ['path' => LengthAwarePaginator::resolveCurrentPath()]
    );

    $otherItems = $others->forPage($currentPage, $perPage);

    // build achievement_images and color_name
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

    $hasColor   = Common::hasInPackV2($this->user->packs, 18, true);
    $color_name = (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVipV2($this->user->id, 18, 'color') : null);

    return [
        'user'  => [
            'user_id'            => $this->user->id,
            'uuid'               => $this->user->uuid ?? '',
            'exp'                => $this->userExp != null
                ? ($this->total_gifts ?? '0')
                : ($data->where($this->key, $this->user->id)->first()->total_gifts ?? '0'),
            'vip_level'          => $this->user->UserVip?->level ?? 0,
            'sender_level'       => $this->user->total_sender_level ?? 0,
            'reciver_level'      => $this->user->total_received_level ?? 0,
            'vip_level_img'      => \App\Helpers\UserPackHelper::getVipIcon($this->user),
            'sender_level_img'   => \App\Helpers\UserLevelHelper::getSenderImage($this->user),
            'reciver_level_img'  => \App\Helpers\UserLevelHelper::getReceiverImage($this->user),
            'type_user'          => intval($this->user->type_user ?? 0),
            'country'            => @$this->user->country,
            'manger_type'        => $this->user->mangerType
                ? new MangerTypeResource($this->user->mangerType)
                : null,
            'age'                => $this->user->profile?->age ?? '',
            'color_name'         => $color_name,
            'achievement_images' => $achievement_images,
        ],
        'top'   => $top, // already padded to 3
        'other' => NewUserRankingResource::collection($otherItems),
    ];
}
}

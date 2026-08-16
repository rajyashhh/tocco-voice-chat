<?php

namespace Modules\Badge\Http\Controllers;

use App\Models\User;
use App\Models\Config;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Badge\Entities\UserBadge;
use Illuminate\Contracts\Support\Renderable;
use Modules\Badge\Http\Resources\UserBadgeResource;

class BadgeController extends Controller
{

    public function index(int $userId)
    {
        if ($userId <= 0) {
            return Common::apiResponse(0, 'Invalid user ID', null, 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return Common::apiResponse(0, 'User not found', null, 404);
        }

        // get user badges from DB
        $userBadges = UserBadge::query()
            ->where('user_id', $userId)
            ->active()
            ->with([
                'badge:id,type',
            ])->get();

        $data = [
            'top' => UserBadgeResource::collection(
                $userBadges->where('badge.type', 'top')
            ),
            'regular' => UserBadgeResource::collection(
                $userBadges->where('badge.type', 'regular')
            ),
        ];

        return Common::apiResponse(1, 'User badges retrieved successfully', $data, 200);
    }



    public function badges($user_types)
    {
        $lang = request()->header('X-localization', 'en');

        $types = [
            'host'         => 1,
            'agency_owner' => 2,
            'shipping'     => 3,
            'bd'           => 4,
        ];

        // keep only requested types
        $types = array_filter($types, function ($id) use ($user_types) {
            return in_array($id, (array) $user_types);
        });

        $suffixes = ['badge'];
        $configNames = [];

        foreach ($types as $type => $id) {
            foreach ($suffixes as $suffix) {
                $localizedName = $suffix === 'badge' ? "{$lang}_{$type}" : "{$lang}_{$type}_{$suffix}";
                $englishName   = $suffix === 'badge' ? "en_{$type}"      : "en_{$type}_{$suffix}";

                $configNames[] = $localizedName;
                $configNames[] = $englishName;
            }
        }

        $configs = Config::whereIn('name', $configNames)->pluck('value', 'name');

        $images = [];
        foreach ($types as $type => $id) {
            foreach ($suffixes as $suffix) {
                $localizedName = $suffix === 'badge' ? "{$lang}_{$type}" : "{$lang}_{$type}_{$suffix}";
                $englishName   = $suffix === 'badge' ? "en_{$type}"      : "en_{$type}_{$suffix}";

                $img = $configs[$localizedName] ?? $configs[$englishName] ?? null;
                if ($img) {
                    $images[] = [
                        'image' => $img,
                        'image_type' => '',
                    ];
                }
            }
        }

        return [
            'top' => array_values($images),
        ];
    }
}

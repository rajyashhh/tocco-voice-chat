<?php

namespace Modules\Public\Http\Services;

use App\Http\Controllers\Api\V1\Auth\LoginController;
use Modules\Vip\Entities\Vip;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Models\Banner;
use App\Models\Config;
use App\Models\Follow;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\EarnedDiamond;
use App\Models\ProfileVisitor;
use App\Models\OfficialMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Public\Entities\UserCounter;
use Modules\Public\Entities\levelInterval;
use Modules\Public\Jobs\RewardWinnerLevel;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use Modules\Public\Events\UnreadCounterGroup;
use Modules\Public\Entities\RewardLevelInterval;
use Modules\Public\Entities\WinnerLevelInterval;
use Modules\Public\Events\UnreadCounterIndividual;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Chat\Entities\ChatMessage;

class UserCounterServices
{
    public function UpgradeDateForType(User $user, $type = null)
    {
        return UserCounter::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
            ],
            [
                'date' => now(),
            ]
        );
    }

    public function getUserCounts(User $user, $type = null)
    {
        $counters = UserCounter::select("id", 'type', 'date', 'user_id')
            ->where("user_id", $user->id)
            ->when($type, function ($query) use ($type) {
                $query->where("type", $type);
            })
            ->get();
        $results = 0;

        foreach ($counters as $counter) {
            $count = $this->getCountByType($user, $counter->type, $counter->date);
            $results = $count;
        }

        return $results;
    }

    public function getUserCountsV2(User $user, array $types = []): array
    {
        $query = UserCounter::select(["id", "type", "date", "user_id"])
            ->where("user_id", $user->id);

        if (!empty($types)) {
            $query->whereIn("type", $types);
        }

        $counters = $query->get();

        $results = [];

        $grouped = $counters->groupBy('type');

        foreach ($types as $type) {
            $items = $grouped->get($type, collect());
            $count = 0;
            foreach ($items as $counter) {
                $count = $this->getCountByType($user, $type, $counter->date);
            }

            $results[$type] = $count;
        }

        return $results;
    }
    /**
     * Get the count of specified type.
     *
     * @param User $user
     * @param string $type
     * @param string $date
     * @return int
     */
    public function getCountByType(User $user, string $type, string $date = null)
    {
        switch ($type) {
            case "system_message":
                return OfficialMessage::where("type", 1)
                    ->where('user_id', $user->id)
                    ->where("created_at", ">", $date)
                    ->count();
            case "official_message":
                return OfficialMessage::where("type", 2)
                    ->where("created_at", ">", $date)
                    ->count();
            case "followeds":
                return Follow::where("followed_user_id", $user->id)
                    ->where("created_at", ">", $date)
                    ->count();
            case "followers":
                return Follow::where("user_id", $user->id)
                    ->where("created_at", ">", $date)
                    ->count();
            case "friend":
                return DB::table('follows as f1')
                    ->selectRaw('
                            LEAST(f1.user_id, f1.followed_user_id) AS user_id,
                            GREATEST(f1.user_id, f1.followed_user_id) AS followed_user_id,
                            MAX(f1.created_at) AS created_at
                        ')
                    ->join('follows as f2', function ($join) {
                        $join->on('f1.user_id', '=', 'f2.followed_user_id')
                            ->on('f1.followed_user_id', '=', 'f2.user_id');
                    })
                    ->where('f1.status', 1)
                    ->where('f2.status', 1)
                    ->groupBy([
                        DB::raw('LEAST(f1.user_id, f1.followed_user_id)'),
                        DB::raw('GREATEST(f1.user_id, f1.followed_user_id)')
                    ])->where(function ($query) use ($user) {
                        // Filter for records involving the authenticated user
                        $query->where('f1.user_id', $user->id)
                            ->orWhere('f1.followed_user_id', $user->id);
                    })->having("created_at", ">", $date)
                    ->count();
            case "visitor":
                return ProfileVisitor::where("user_id", $user->id)
                    ->where("created_at", ">", $date)
                    ->count();
            case "mybag":
                return Pack::whereUserId($user->id)
                    ->where("created_at", ">", $date)
                    ->count();
            case "mall":
                $ware =   Ware::query()->where("created_at", ">", $date)->where('enable', 1)
                    ->whereIn('get_type', [4, 6])->count();
                return $ware;
            case 'message':
                return ChatMessage::where('user_id', $user->id)->where('status', 'received')->count();
            default:
                return 0;
        }
    }

    public function eventUser(User $user, $type = null, $counter = null)
    {
        try {
            event(new UnreadCounterIndividual($type, $user, $counter));
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function eventUsers($type = null, $counter = null)
    {
        try {
            event(new UnreadCounterGroup($type, $counter));
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}

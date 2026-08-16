<?php

namespace Modules\Moment\Http\Repositories;

use App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentLikes;
use Modules\Moment\Entities\ReportMoment;

class MomentRepository
{

    public function findReportMomentById($id)
    {
        return ReportMoment::find($id);
    }

    public function deleteReportMoment(ReportMoment $reportMoment)
    {
        return $reportMoment->delete();
    }

    public function findMomentById($id)
    {
        return Moment::find($id);
    }

    public function deleteMoment(Moment $moment)
    {
        return $moment->delete();
    }

    public function getMomentById($id, $userId)
    {
        return Moment::where('id', $id)
            ->likeExists($userId)->with('images')
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->with(['gifts' => function ($query) {
                $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                    ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
            }])
            ->first();
    }

    public function createMoment(array $data)
    {
        return Moment::create($data);
    }

    public function getUserMoments($userId, $page)
    {
        $authUserId = auth()->id();

        return Moment::where('user_id', $userId)
            ->whereHas('user')->with('images')
            ->likeExists($userId)
            ->withCount(['likes', 'comments'])
            ->with(['gifts' => function ($query) {
                $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                    ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
            }])
            ->with(['user.chatRoomsAsUser' => function ($q) use ($authUserId) {
                $q->where('user_id2', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }, 'user.chatRoomsAsUser2' => function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }])
            ->orderBy('created_at', 'desc')
            // ->when($page == 1, function ($query) {
            //     $seed = rand(1000, 2000);
            //     $query->orderBy(DB::raw('RAND(' . $seed . ')'));
            // })
            ->paginate(10);
    }

    public function getLikedMoments($userId, $page)
    {
        return MomentLikes::with([
            'moment' => function ($query) use ($userId) {
                $query->likeExists($userId)->with('images')
                    ->withCount(['likes', 'comments'])
                    ->with(['gifts' => function ($query) {
                        $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                            ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
                    }]);
            }
        ])
            ->where('user_id', $userId)
            ->orderByRaw('YEAR(created_at) DESC')
            ->orderByRaw('MONTH(created_at) DESC')
            ->when($page == 1, function ($query) {
                $seed = rand(1000, 2000);
                $query->orderBy(DB::raw('RAND(' . $seed . ')'));
            })
            ->paginate(10);
    }

    public function getFollowedMoments($userId, $page)
    {
        return Follow::whereHas('moments')
            ->with([
                'moments.user',
                'moments' => function ($query) use ($userId) {
                    $query->likeExists($userId)->with('images')
                        ->withCount(['likes', 'comments'])
                        ->with(['gifts' => function ($query) {
                            $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                                ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
                        }])
                        ->orderByRaw("CASE WHEN (SELECT COUNT(*) FROM moment_user_likes WHERE moment_user_likes.moment_id = moment.id AND moment_user_likes.user_id = $userId) > 0 THEN 1 ELSE 0 END ASC");
                }
            ])
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function getAllMoments($userId, $page)
    {
        $authUserId = auth()->id();

        return Moment::likeExists($userId)
            ->whereHas('user')->with('images')
            ->with(['user.chatRoomsAsUser' => function ($q) use ($authUserId) {
                $q->where('user_id2', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }, 'user.chatRoomsAsUser2' => function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }])
            ->withCount(['likes', 'comments'])
            ->with([ 'gifts' => function ($query) {
                $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                    ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
            }])
            ->orderByRaw("CASE WHEN (SELECT COUNT(*) FROM moment_user_likes WHERE moment_user_likes.moment_id = moment.id AND moment_user_likes.user_id = $userId) > 0 THEN 1 ELSE 0 END ASC")
            ->when($page == 1, function ($query) {
                $seed = rand(1000, 2000);
                $query->orderBy(DB::raw('RAND(' . $seed . ')'));
            })->paginate(10);
    }


    public function getNewMoments($userId)
    {
        $authUserId = auth()->id();

        return Moment::likeExists($userId)
            ->with(['user.chatRoomsAsUser' => function ($q) use ($authUserId) {
                $q->where('user_id2', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }, 'user.chatRoomsAsUser2' => function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }])
            ->whereHas('user')->with('images')
            ->withCount(['likes', 'comments'])
            ->with([ 'gifts' => function ($query) {
                $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                    ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
            }])
            ->orderByRaw("CASE WHEN (SELECT COUNT(*) FROM moment_user_likes WHERE moment_user_likes.moment_id = moment.id AND moment_user_likes.user_id = $userId) > 0 THEN 1 ELSE 0 END ASC")
            ->take(10)->orderByDesc('id')->paginate(10);
    }


    public function momentUserFollow($userId)
    {
        $authUserId = auth()->id();
        $followId = Follow::where('user_id', $userId)->pluck('followed_user_id');

        return Moment::likeExists($userId)->whereIn('user_id', $followId)
            ->whereHas('user')->with('images')
            ->withCount(['likes', 'comments'])
            ->with(['user.chatRoomsAsUser' => function ($q) use ($authUserId) {
                $q->where('user_id2', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }, 'user.chatRoomsAsUser2' => function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            }])
            ->with(['gifts' => function ($query) {
                $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                    ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
            }])
            ->orderByRaw("CASE WHEN (SELECT COUNT(*) FROM moment_user_likes WHERE moment_user_likes.moment_id = moment.id AND moment_user_likes.user_id = $userId) > 0 THEN 1 ELSE 0 END ASC")->paginate(10);
    }
}

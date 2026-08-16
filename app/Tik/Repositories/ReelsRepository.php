<?php

namespace App\Tik\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Illuminate\Support\Facades\Auth;
use Modules\Reals\Entities\Real;

class ReelsRepository extends AbstractRepository
{

    public function __construct()
    {
        $model = new Real();
        parent::__construct($model);

        if (!$this->model instanceof Real) return;
    }

    private function baseQuery()
    {
        $userId = Auth::id();

        return $this->model
            ->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])
            ->withCount('likes')
            ->withExists(['likes as is_liked' => fn($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at');
    }

    public function all($perPage, $Page)
    {
        return $this->baseQuery()
            ->paginate($perPage ?? 10, ['*'], 'page', $Page ?? 1);
    }

    public function following($userId, $perPage = 10, $page = 1)
    {
        // Reels from users that the current user follows
        $followingIds = \DB::table('follows')
            ->where('user_id', $userId)
            ->pluck('follow_id');

        return $this->baseQuery()
            ->whereIn('user_id', $followingIds)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function showByUser($id)
    {
        return $this->baseQuery()
            ->where('user_id', $id)
            ->paginate(10);
    }

    public function find($id)
    {
        $userId = Auth::id();

        return $this->model->query()
            ->where('id', $id)
            ->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])
            ->withCount('likes')
            ->withExists(['likes as is_liked' => fn($q) => $q->where('user_id', $userId)])
            ->first();
    }

    public function search($input)
    {
        return $this->baseQuery()
            ->whereHas('user', fn($q) => $q->fitterByUuid(trim($input)))
            ->get();
    }

    public function incrementViews($reelId)
    {
        $this->model->where('id', $reelId)->increment('views_count');
    }

    public function delete($reel)
    {
        $reel->delete();
        return true;
    }
}

<?php

namespace Modules\Reals\Http\Services;

use App\Models\User;
use Modules\Reals\Entities\Real;

class RealViewsService extends BaseModelService
{

    public function add(Real $real, User $user)
    {
        $userId = $user->id;
        $real->views()->create([
            'user_id' => $userId,
        ]);
        return true;
    }


    public function delete($like_id, Real $real)
    {
        $real->likes()->where('id', $like_id)->delete();
    }

    /**
     * @param $real
     * @return mixed
     */
    public function showLikes($real)
    {
        return $real->likes()->with([
            'user' => function ($query) {
                $query->withoutAppends()->with('profile')->select(['id', 'name']);
            }
        ])->paginate(10);
    }
}

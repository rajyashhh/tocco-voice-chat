<?php

namespace Modules\Reals\Http\Services;

use App\Models\User;
use Modules\Reals\Entities\Real;
use App\Facades\CustomNotification;
use Illuminate\Database\Eloquent\Model;

class RealLikesService extends BaseModelService
{

    public function __construct(Model $model)
    {
        parent::__construct($model);
    }

    public function add(Real $real, User $user)
    {
        $userId = $user->id;
        $real->likes()->create([
            'user_id' => $userId,
        ]);
        return true;
    }

    public function likeOrUnLike(Real $real, User $user)
    {
        $userId = $user->id;
        $likeData  = $real->likes()->where('user_id', $userId)->first();
        if ($likeData) {
            $likeData->delete();
        } else {
            $real->likes()->create([
                'user_id' => $userId,
            ]);
            CustomNotification::likeReal($real, $user);
        }
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

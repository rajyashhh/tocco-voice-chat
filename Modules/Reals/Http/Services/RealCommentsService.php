<?php

namespace Modules\Reals\Http\Services;

use App\Models\User;
use Modules\Reals\Entities\Real;

class RealCommentsService
{

    public function add($data, Real $real, User $user)
    {
        $comment = $data['comment'];
        $userId = $user->id;
        $real->comments()->create([
            'user_id' => $userId,
            'comment' => $comment
        ]);
        return true;
    }


    public function delete($comment_id, Real $real)
    {
        $real->comments()->where('real_user_comments.id', $comment_id)->delete();
    }

    /**
     * @param $real
     * @return mixed
     */
    public function showComments($real)
    {
        return $real->comments()->with([
            'user' => function ($query) {
                $query->withoutAppends()->with('profile')->select(['id', 'name']);
            }
        ])->orderByDesc('id')->paginate(10);
    }
}

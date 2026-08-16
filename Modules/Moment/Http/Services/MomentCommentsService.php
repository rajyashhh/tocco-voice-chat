<?php

namespace Modules\Moment\Http\Services;

use App\Models\User;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentCommint;

class MomentCommentsService
{

    public function add($data, Moment $moment, User $user)
    {
        $comment = $data['comment'];
        $userId = $user->id;
        $moment->comments()->create([
                                      'user_id' => $userId,
                                      'comment' => $comment
                                  ]);
        return true;
    }


    public function delete($comment_id, Moment $moment)
    {

        $commint =  MomentCommint::findOrFail($comment_id);
         if (!$commint ) {
            return "false";
         }
        $moment->comments()->where('moment_user_comments.id', $comment_id)->delete();



    }

    /**
     * @param $moment
     * @return mixed
     */
    public function showComments($moment)
    {
        return $moment->comments()
        ->has('user') 
        ->with([
            'user' => function ($query) {
                $query->withoutAppends()->with('profile')->select(['id', 'uuid', 'name']);
            }
        ])->orderByDesc('id')->paginate(10);
    }

}

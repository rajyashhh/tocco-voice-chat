<?php

namespace App\Tik\Repositories;


use App\Models\GroupChat;

class GroupChatRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new GroupChat());
    }

    // public function getWithPaginate()
    // {
    //     return $this->model->whereHas('user')->with('user','parent')->orderBy('created_at', 'DESC')->paginate(10);
    // }

    public function getWithPaginate()
    {
        return $this->model->whereHas('user')->with([
            'user.profile:id,user_id,avatar,gender',
            'user.UserVip',
            'user.receiverLevel',
            'user.senderLevel',
            'user.packs.ware',

            // 'parent',
            'user.packs'  => fn($q) => $q->whereIn('type', [25, 18, 4])->where('is_used', true)->with('ware:id,value'),
            'parent.user.profile:id,user_id,avatar',
            'parent.user.UserVip',
            'parent.user.receiverLevel',
            'parent.user.senderLevel',
            'parent.user.packs.ware',
            'parent.user.packs' => fn($q) => $q->whereIn('type', [25, 1814])->where('is_used', true)->with('ware:id,value'),
        ])->orderBy('created_at', 'DESC')->paginate(10);
    }
}

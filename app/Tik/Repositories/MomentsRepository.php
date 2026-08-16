<?php

namespace App\Tik\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Moment\Entities\Moment;


class MomentsRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new Moment());
    }

    public function all($id, $perPage, $page)
    {
        return $this->model->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])->paginate($perPage, ['*'], 'page', $page);
    }
 
    public function find($id)
    {
        return $this->model->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])->find($id);
    }





    public function search($input)
    {
        $query = $this->model->query();

        $query->whereHas('user', function ($query) use ($input) {
            $query->where('uuid', trim($input));
        })->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar']);

        $result = $query->get();

        return $result;
    }

    public function get_user_moments($user_id)
    {
        $query = $this->model->query();

        $query->whereHas('user', function ($query) use ($user_id) {
            $query->where('id', trim($user_id));
        })->with(['comments', 'likes'])
        ->with(['gifts' => function ($query) {
            $query->select(DB::raw('sum(moment_user_gifts.num) as gifts_count'))
                  ->groupBy('moment_user_gifts.moment_id', 'moment_user_gifts.gift_id');
        }]);

        $result = $query->paginate(10);

        return $result;
    }


    
}

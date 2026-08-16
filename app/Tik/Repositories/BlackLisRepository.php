<?php

namespace App\Tik\Repositories;

use App\Models\BlackList;
use Illuminate\Support\Facades\DB;
use Modules\Moment\Entities\Moment;


class BlackLisRepository extends AbstractRepository
{
    
    public function __construct()
    {
        parent::__construct(new BlackList());
    }


    public function list($key,$perPage,$page){

        return $this->model
                            ->when(!empty($key), function ($query) use ($key) {
                                $query->whereHas('user', function ($subQuery) use ($key) {
                                    $subQuery->where('uuid', 'like', "%{$key}%")
                                            ->orWhere('name', 'like', "%{$key}%");
                                });
                            })
                          ->select('id','user_id')
                          ->has('user')
                          ->with('user:id,name,uuid')
                          
                          ->paginate($perPage, ['*'], 'page', $page);
   
    }
   

    // public function search($key){
    //     return $this->model->with('user')->whereHas('user', function ($query) use ($key) {
    //         $query->where('uuid', 'like', "%$key%")
    //         ->orWhere('name', 'like', "%$key%");
    //     })->get()->unique('user_id')->values();
        
    // }

    // public function blocked_search($user_id,$key){
    //     return $this->model->where('user_id',$user_id)
    //     ->with('blockedPerson')->whereHas('blockedPerson', function ($query) use ($key) {
    //         $query->where('uuid', 'like', "%$key%")
    //         ->orWhere('name', 'like', "%$key%");
    //     })->get()->unique('user_id')->values();
        
    // }
    
    public function store( array $data){
           return $this->create($data);   
    }
    public function black_lists($userid,$key,$perPage,$page){
        return $this->model
        ->when(!empty($key), function ($query) use ($key) {
            $query->whereHas('blockedPerson', function ($subQuery) use ($key) {
                $subQuery->where('uuid', 'like', "%{$key}%")
                        ->orWhere('name', 'like', "%{$key}%");
            });
        })
        ->with('blockedPerson')->whereHas('user', function ($query) use ($userid) {
            $query->where('id', 'like', "%$userid%");
        })->paginate($perPage, ['*'], 'page', $page);   
    }

    public function delete($id){
        return $this->delete($id);   
    }
    
}

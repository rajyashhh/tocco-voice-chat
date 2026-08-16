<?php
namespace App\Repositories\User;
use App\Models\User;

class UserRepo implements UserRepoInterface {

    protected $model;

    public function __construct (User $model)
    {
        $this->model = $model;
    }

    public function all ( $req )
    {
        $result = $this->model->where(function ($q) use ($req){
            if ($search = $req->search){
                $req->where('name',$search)->orWhere('id',$search)->orWhere('nickname',$search);
            }
        })->orderBy($req->ord?:'id',$req->sort?:'DESC');

        if ($pp = $req->pp){ // pp = perPage
            return $result->paginate($pp);
        }
        return $result->get();
    }

    public function find ( $req , $id )
    {
        // TODO: Implement find() method.
    }

    public function create ( $req )
    {
        // TODO: Implement create() method.
    }

    public function update ( $req , $id )
    {
        // TODO: Implement update() method.
    }

    public function delete ( $id )
    {
        // TODO: Implement delete() method.
    }
}

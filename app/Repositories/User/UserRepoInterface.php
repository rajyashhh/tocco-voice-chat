<?php
namespace App\Repositories\User;
interface UserRepoInterface{
    public function all($req);
    public function find($req,$id);
    public function create($req);
    public function update($req,$id);
    public function delete($id);
}

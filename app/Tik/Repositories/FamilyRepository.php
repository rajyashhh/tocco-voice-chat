<?php

namespace App\Tik\Repositories;

use App\Models\Family;
use App\Tik\Repositories\AbstractRepository;
use Illuminate\Database\Eloquent\Model;

class FamilyRepository extends AbstractRepository
{

    public function __construct()
    {
        $model = new Family;
        parent::__construct($model);

        if (!$this->model instanceof Family) return;
    }

    public function getWithSearch($search = null)
    {
        $conditions = [['status', 1]];
        if (!is_null($search))   $conditions[] =  ['name', 'like', "%$search%"];
        return $this->getPaginate(
            conditions: $conditions,
            with: [
                'members' => fn ($q) => $q->limit(10),
                'owner' => fn($q) => $q->with(['profile:id,user_id,avatar', 'country:id,name,flag']),
            ],
            withCount: ['members', 'usersRequests']
        );
    }

    public function findById($id)
    {
        return $this->model->query()
            ->with([
                'owner' => fn($q) => $q->with([
                    'profile:id,user_id,avatar',
                    'country:id,name,flag',
                    'mangerType',
                    'specialId.ware',
                ]),
                'allMembers.user',
            ])
            ->withCount(['members', 'allMembers'])
            ->find($id);
    }

    // public function create($data)
    // {
    //     return $this->model->create([
    //         'name' => $data['name'],
    //         'introduce' => $data['introduce'],
    //         'notice' => $data['notice'],
    //         'user_id' => $data['user_id'],
    //         'num'     => $data['num'],
    //         'image' => $data['image'],
    //         'is_success' => $data['is_success'],

    //     ]);
    // }

    public function findByUserId($userId)
    {
      return  $this->model->query()->where('user_id', $userId)->first();
    }

    public function delete($family)
    {
        $family->delete();
        return true;
    }
}

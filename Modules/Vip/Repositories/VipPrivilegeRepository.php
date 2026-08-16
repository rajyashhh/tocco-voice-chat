<?php

namespace Modules\Vip\Repositories;

use Modules\Vip\Entities\VipPrivilege;
use App\Tik\Repositories\AbstractRepository;
use Illuminate\Support\Collection;

class VipPrivilegeRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new VipPrivilege());
    }

    /**
     * Get all vip privileges
     */
    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }

    /**
     * Find vip privilege by id
     */
    public function findById(int $id): ?VipPrivilege
    {
        return $this->model->find($id);
    }

    /**
     * Get list of VIP privileges for select/search components
     */
    public function listVip(?string $search = null): Collection
    {
        return $this->model->newQuery()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('id', $search);
            })
            ->select('id', 'name', 'img1', 'img2')
            ->get();
    }

    public function getAllPrivileges()
    {
        return VipPrivilege::all();
    }
}

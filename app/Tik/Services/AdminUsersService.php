<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use App\Facades\ManagerHelper;
use Illuminate\Support\Facades\Hash;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\AgencyRepository;
use App\Tik\Repositories\AdminUsersRepository;


class AdminUsersService
{
    public function __construct(
        private readonly AdminUsersRepository $adminUsersRepository,
        private readonly AgencyRepository $agencyRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function index($id, $perPage, $page)
    {
        $adminUsers = $this->adminUsersRepository->all($id, $perPage, $page);
        $adminUsers->each(function ($admin) {
            $admin->salary = ManagerHelper::getTotalAgenciesSalary($admin->managerAgencies, $admin->app_id);
            $admin->agencyCount = $this->agencyRepository->countAgencyUserAdmin($admin->app_id);
        });
        return $adminUsers;
    }

    public function create($request)
    {
        $userModel = new (config('admin.database.users_model'));
        $userModel->username = $request->username;
        $userModel->name = $request->name;
        $userModel->password = Hash::make($request->password);
        $userModel->app_id = $request->app_id;
        $userModel->Agency_manger = true;
        $image = null;
        if ($request->hasFile('image')) {
            $image = Common::upload('images', $request->file('image'));
        }
        $userModel->avatar = $image;
        $userModel->save();
        $this->userRepository->updateManger($request->app_id);
        $userModel->roles()->attach(['role_id' => 13, 'user_id' => $userModel->id]);
        \App\Models\Admin::forgetCachedPermissionsFor($userModel->id);
        return true;
    }

    public function show($adminUserId)
    {
        $adminUser = $this->adminUsersRepository->findById($adminUserId);
        $agencies = $this->agencyRepository->getByAgencyMangerId($adminUser->app_id);
        $agencies->each(function ($agency) {
            $agency->userCount = $this->userRepository->countByAgencyId($agency->id);
        });
        return $agencies;
    }

    public function showUserAgency($agencyId, $perPage, $page)
    {
        return $this->userRepository->findUsersByAgencyId($agencyId, $perPage, $page);
    }
}

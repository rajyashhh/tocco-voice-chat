<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Agency;
use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FilterChargeService
{
    public int $perPage;
    public int $currentPage;

    public function __construct($perPage, $currentPage)
    {
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
    }

    public function result($type, $key): LengthAwarePaginator|bool
    {
        $actions = [
            'user' => fn() => $this->users($key),
            'host_agency' => fn() => $this->agencies($key),
            'agency' => fn() => $this->shippingAgencies($key),
            'bd' => fn() => $this->bd($key),
            'dash' => fn() => $this->admins($key),
        ];

        if (isset($actions[$type])) {
            return $actions[$type]();
        }

        return 0;
    }

    public function users($key): LengthAwarePaginator
    {
        return User::query()->where(function ($query) use ($key) {
            $query->where('name', 'like', '%' . $key . '%')
                ->orWhere('uuid', 'like', '%' . $key . '%')
                ->orWhere('id', 'like', '%' . $key . '%')
                ->orWhere('special_id', 'like', '%' . $key . '%');
        })
            ->select(['id', DB::raw('concat(name , " - ", uuid) as name')])
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }

    public function agencies($key): LengthAwarePaginator
    {
        return Agency::selectRaw('concat(name, " - ", id) as name, id')
            ->where('type', 1)
            ->where(function ($query) use ($key) {
                $query->where('name', 'like', '%' . $key . '%')
                    ->orWhere('id', 'like', '%' . $key . '%');
            })
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }

    public function shippingAgencies($key): LengthAwarePaginator
    {
         return ShippingAgency::selectRaw('concat(name, " - ", id) as name, id')
             ->where('type', 2)
             ->where(function ($query) use ($key) {
                $query->where('name', 'like', '%' . $key . '%')
                    ->orWhere('id', 'like', '%' . $key . '%');
            })
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }

    public function bd($key): LengthAwarePaginator
    {
        return User::query()->where('id_bd', 1)->where(function ($query) use ($key) {
            $query->where(function ($query) use ($key){
                    $query->where('name', 'like', '%' . $key . '%')
                        ->orWhere('uuid', 'like', '%' . $key . '%')
                        ->orWhere('id', 'like', '%' . $key . '%')
                        ->orWhere('special_id', 'like', '%' . $key . '%');
                });
        })
            ->select(['id', DB::raw('concat(name , " - ", uuid) as name')])
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }

    public function admins($key): LengthAwarePaginator
    {
        return Admin::query()->where(function ($query) use ($key) {
            $query->where(function ($query) use ($key){
                    $query->where('name', 'like', '%' . $key . '%')
                        ->orWhere('id', 'like', '%' . $key . '%')
                        ->orWhere('username', 'like', '%' . $key . '%');
                });
        })
            ->select(['id', DB::raw('concat(name , " - ", id) as name')])
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }
}

<?php

namespace App\Tik\Repositories;

use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\AgencyUserJob;
use App\Models\Scopes\HostAgencyScope;
use App\Models\ShippingAgency;
use App\Models\UsersJoinedAgency;
use Carbon\Carbon;

class AgencyRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Agency());
    }

    public function findByOwner($ownerId, $status = null)
    {
        $data =  $this->model->where('app_owner_id', $ownerId);
        if ($status) $data->where('status', $status);
        return  $data->first();
    }

    public function findAgencyByOwnerId($ownerId, $status = null)
    {
        $data = $this->model->where('app_owner_id', $ownerId);
        if ($status) $data->where('status', $status);
        return  $data->first();
    }

    public function find($id)
    {
        // return $this->model->find($id);
        $shipping = ShippingAgency::find($id);
        if ($shipping) {
            return $shipping;
        }
        return Agency::find($id);
    }

    public function filterAgency($id)
    {
        return $this->model->whereRaw('CAST(id AS CHAR) LIKE ?', [$id . '%'])->with('owner', 'AgencypaymentGateways')->get();
    }

    public function findById($id)
    {
        return $this->model
            ->with([
                'additionalInfo', 'mempers', 'userSalaries',
                'owner.profile',
                'admins.user.profile', 'admins.user.packs', 'admins.user.specialId.ware',
            ])
            ->withCount('mempers')
            ->where('id', $id)->first();
    }

    public function gitOldAgencies($id)
    {
        return UsersJoinedAgency::where('user_id', $id)
            ->whereHas('agency')
            ->with(['agency:id,img'])
            ->select('join_date', 'leave_date', 'agency_id')
            ->get();
    }


    public function findByStatus($id)
    {
        return $this->model->with('additionalInfo')->where('id', $id)->where('status', 1)->first();
    }

    public function findFomAll($id)
    {
        $shipping = ShippingAgency::find($id);
        if ($shipping) {
            return $shipping;
        }
        return Agency::find($id);
    }

    public function findAllByStatus($id)
    {
        $shipping = ShippingAgency::with('additionalInfo')
            ->where('id', $id)
            ->where('status', 1)
            ->first();

        if ($shipping) {
            return $shipping;
        }
        return Agency::with('additionalInfo')
            ->where('id', $id)
            ->where('status', 1)
            ->first();
    }


    public function members($agency, $perPage = 20, $page = 1)
    {
        // A host who is also an agency operator (requestManger) must still appear
        // in the members list (owner decision) — they show in BOTH members and the
        // operators tab. The old whereDoesntHave('agencyAdmins') wrongly hid every
        // such host. The orderBy('users.id') tiebreaker keeps pagination
        // deterministic (no cross-page duplicates/gaps when monthly sums tie/are NULL).
        return $agency->mempers()->where('id', '!=', $agency->app_owner_id)->withSum(['monthlyDiamondReceive as monthly_diamond_received_sum' => function ($q) {
        $q->where('month', now()->month)
          ->where('year', now()->year);
    }], 'monthly_diamond_received')->orderByDesc('monthly_diamond_received_sum')->orderBy('users.id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function userMembers($agency, $type = null, $userIds = null)
    {
        $members = $agency?->mempers?->pluck("id")->toArray();
        return  $members;
    }

    public function getWithSelectMonthAndYear($agencyId)
    {
        return $this->model->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, created_at')->where('id', $agencyId)->first();
    }

    public function updateAgency($agency)
    {
        $agency->save();
        return true;
    }

    public function updateStatus($agency, $status)
    {
        $agency->status = $status;
        $this->updateAgency($agency);
        return true;
    }

    public function getByAdditionalInfo()
    {
        return $this->model->where('status', 0)->whereHas('additionalInfo', function ($query) {
            $query->where('status', 0);
        })->with('additionalInfo')->get();
    }

    public function getActiveAgency($id, $perPage, $page)
    {
        return $this->model->withoutGlobalScope(HostAgencyScope::class)->where(function ($query) {
            $query->WhereDoesntHave('additionalInfo')->orWhereHas(
                'additionalInfo',
                function ($query) {
                    $query->where('status', 1);
                }
            );
        })->orderByDesc('id')->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }

    public function getAllActiveAgency($id)
    {
        return  $this->model->withoutGlobalScope(HostAgencyScope::class)->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->where(function ($query) {
            $query->WhereDoesntHave('additionalInfo')->orWhereHas(
                'additionalInfo',
                function ($query) {
                    $query->where('status', 1);
                }
            );
        })->orderByDesc('id')->get();
    }

    public function agencyById($id)
    {
        return  $this->model->where(function ($query) {
            $query->WhereDoesntHave('additionalInfo')->orWhereHas(
                'additionalInfo',
                function ($query) {
                    $query->where('status', 1);
                }
            );
        })->findOrFail($id);
    }

    public function getByAdditionalInfoPaginate($id, $uuid, $perPage, $page, $status = null, $action = null)
    {
        if ($action == null) {
            $agencies =    $this->model->withoutGlobalScope(HostAgencyScope::class)->where('status', 0)->whereHas('additionalInfo', function ($query) {
                $query->where('status', 0);
            });
        } else {
            $agencies =    $this->model->withoutGlobalScope(HostAgencyScope::class)->where('status', '!=', 0)->whereHas('additionalInfo', function ($query) {
                $query->where('status', '!=', 0);
            });
        }
        $agencies =    $agencies->whereHas('owner', function ($query) use ($uuid) {
            $query->when(isset($id), function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            });
        })->with('additionalInfo', 'owner')->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->when(isset($status), function ($query) use ($status) {
            $query->where('status', $status);
        })->orderByDesc("id")->paginate($perPage, ['*'], 'page', $page);
        return $agencies;
    }

    public function getAgencyByFilter($keyword)
    {
        $year = request('year') ?? Carbon::now()->year;
        $month = request('month') ?? Carbon::now()->month;

        return  $this->model
            ->with(['giftLogs' => function ($q) use ($year, $month){
                $q->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->with('receiver');
            }, 'owner.profile', 'mempers.profile', 'admins.user.profile', 'admins.user.packs',
                'admins.user.specialId.ware', 'joinRequests', 'giftLogs.receiver.packs', 'giftLogs.receiver.profile',
                'giftLogs.receiver.specialId.ware'])
            ->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%');
            })
            ->orderBy('id')
            ->cursorPaginate(request('per_page', 10));
//            ->paginate(10);
    }

    public function countAgencyUserAdmin($userId)
    {
        return  $this->model->where('agency_manger_id', $userId)->count();
    }

    public function getByAgencyMangerId($agencyMangerId)
    {
        return  $this->model->where('agency_manger_id', $agencyMangerId)->with('owner')->get();
    }

    public function getAdminByUserId($userId)
    {
        return AgencyUserJob::where('user_id', $userId)->where('type', 'requestManger')->first();
    }

    public function getAgencyById($agencyId)
    {
        return Agency::where('id', $agencyId)->first();
    }

    public function getAgencyByOwnerId($ownerId)
    {
        return Agency::where('app_owner_id', $ownerId)->first();
    }

    public function getAgencyByOwnerIdAndType($ownerId, $type)
    {
        return Agency::where('app_owner_id', $ownerId)
            ->where('type', $type)
            ->where('status', 1)
            ->first();
    }

    public function getJoinRequests($agencyId)
    {
        return AgencyJoinRequest::where('agency_id', $agencyId);
    }

    public function getByIds($ids)
    {
        return  $this->model->withoutGlobalScope(HostAgencyScope::class)->whereIn('id', $ids)->get();
    }

    public function agencies($id, $search, $perPage, $page)
    {
        return  $this->model->withoutGlobalScope(HostAgencyScope::class)->where('id', '!=', $id)->where(function ($query) {
            $query->WhereDoesntHave('additionalInfo')->orWhereHas(
                'additionalInfo',
                function ($query) {
                    $query->where('status', 1);
                }
            );
        })->when(isset($search), function ($query) use ($search) {
            $query->whereHas('owner', function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('uuid', 'like', "%$search%");
            });
        })->with('owner')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function report($id, $month = null, $year = null, $perPage, $page)
    {
        return $this->model
            ->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })
            ->whereHas('agencySalaries', function ($q) use ($month, $year) {
                $q->when(isset($month) && isset($year), function ($query) use ($month, $year) {
                    $query->where('month', $month)->where('year', $year);
                });
            })
            ->withCount('users')
            ->with(['owner', 'dashOwner', 'agencySalaries' => function ($query) use ($month, $year) {
                $query->when(isset($month) && isset($year), function ($query) use ($month, $year) {
                    $query->where('month', $month)->where('year', $year);
                });
            }])
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($agency) use ($month, $year) {
                $agency->target = $agency->getTotalSallaryAgency($month, $year);
                $agency->expenses = $agency->getTotalCutAmountAgency($month, $year);
                $agency->salary = $agency->getSalaryAgency($month, $year);
                return $agency;
            });
    }

    public function getChargeAgency($id)
    {
        return $this->model->withoutGlobalScope(HostAgencyScope::class)->whereHas('chargeAgency')->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->get();
    }
}

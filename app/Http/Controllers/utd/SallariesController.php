<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\SalaryTrx;
use App\Models\UsdTransfer;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\UserTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SallariesController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'users'); // Default to 'users'

        if ($type === 'users') {
            return Common::apiResponse(true, 'Success', $this->getUsersGrid());
        } elseif ($type === 'agencies') {
            return Common::apiResponse(true, 'Success', $this->getAgenciesGrid());
        }

        return Common::apiResponse(false, 'Invalid type');
    }

    public function details(Request $request)
    {
        $type = $request->query('type', 'users'); // Default to 'users'

        if ($type === 'users') {
            return Common::apiResponse(true, 'Success', $this->getUserDetails($request));
        } elseif ($type === 'agencies') {
            return Common::apiResponse(true, 'Success', $this->getAgencyDetails($request));
        }

        return Common::apiResponse(true, 'Invalid type');
    }

    private function getUserDetails(Request $request)
    {
        $uuid = $request->uuid;
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);
        $user = null;
        if ($uuid) {
            $user = User::where('uuid', $uuid)->first();

            if (!$user) {
                return ['message' => 'User not found'];
            }
        }


        $userSalaries = UserSallary::when(isset($user), function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereHas('user', function ($q) {
                $q->where('agency_id', '!=', 0);
            })
            ->select(DB::raw('sum(sallary) as totalTarget2'), DB::raw('sum(sallary - cut_amount) as totalSalary2'))
            ->first();

        $userCutAmount = UserSallary::when(isset($user), function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where(function ($query) use ($year, $month) {
            $query->where(DB::raw('concat(year,"-", month)'), '<=', "$year-$month");
        })->whereHas('user', function ($q) {
            $q->where('agency_id', '!=', 0);
        })->select(DB::raw('sum(cut_amount) as totalPayments'))->first();

        $totalDiamonds = UserTarget::when(isset($user), function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->sum(DB::raw('user_diamonds'));

        return [
            'diamond' => $totalDiamonds ?? 0,
            'target' => $userSalaries->totalTarget2 ?? 0,
            'salary' => $userSalaries->totalSalary2 ?? 0,
            'payments' => $userCutAmount->totalPayments ?? 0,
        ];
    }

    private function getAgencyDetails(Request $request)
    {
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);
        $agencyId = $request->query('id');

        $agencySallary = AgencySallary::where(function ($query) use ($year, $month) {
            $query->where(DB::raw('concat(year,"-", month)'), '<=', "$year-$month");
        })->when($agencyId, function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->select(DB::raw('sum(sallary) as totalTarget'), DB::raw('sum(cut_amount) as totalPayments'), DB::raw('sum(sallary - cut_amount) as totalSallary'))
            ->first();

        return [
            'target' => $agencySallary->totalTarget ?? 0,
            'salary' => $agencySallary->totalSallary ?? 0,
            'payments' => $agencySallary->totalPayments ?? 0,
        ];
    }
    public function pay(Request $request)
    {
        $amount = \request('amount');
        try {
            DB::beginTransaction();
            if ($request->type == "user") {
                $user = User::findOrFail(\request('id'));
                $amount = $amount ?? $user->salary;
                $agencyId = $user->agency_id;
                $userId = $user->id;

                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount + $amount"),
                        'pending_dollar' => DB::raw("pending_dollar - $amount")
                    ]
                );
            } elseif ($request->type == "agency") {
                $agency = Agency::findOrFail(\request('id'));
                $agencyId = $agency->id;
                $amount = $amount ?? $agency->salary;
                $userId = null;

                AgencySallary::updateOrCreate(
                    [
                        'agency_id' => $agency->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount + $amount"),
                    ]
                );
            }


            UsdTransfer::create([
                // "admin_id"  => request('admin_id'),
                "user_id"   => $userId,
                "agency_id" => $agencyId,
                "user_type" => \request('type') == 'agency' ? 1 : 0,
                "value"     => $amount
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(false, $exception->getMessage());
        }

        return Common::apiResponse(true, 'Success');
    }
    public function cashing(Request $request)
    {
        $amount = \request('amount');

        try {
            DB::beginTransaction();
            $type = \request('type') ?? 'user';
            if (\request('id') && $type == 'agency') {
                $agency = Agency::query()->find(\request('id'));
                if ($agency) {
                    if ($request->select_type == 'decrement') {
                        if ($agency->salary < $amount) {
                            return Common::apiResponse(false, __('low balance'));
                        }
                    }

                    $m = $amount ?: $agency->salary;
                    $userSallary = AgencySallary::where('agency_id', \request('id'))->latest('created_at')->first();

                    if (!$userSallary) {
                        $userSallary = new AgencySallary();
                        $userSallary->agency_id = \request('id');
                        $userSallary->sallary = 0;
                        if ($request->select_type == 'increment') {
                            $userSallary->cut_amount = -$m;
                        }
                        if ($request->select_type == 'decrement') {
                            $userSallary->cut_amount = $m;
                        }
                        $userSallary->month = now()->month;
                        $userSallary->year = now()->year;
                        $userSallary->is_paid = 0;
                        $userSallary->save();
                    } else {
                        if ($request->select_type == 'increment') {
                            $userSallary->cut_amount += -$m;
                            $userSallary->update();
                        }
                        if ($request->select_type == 'decrement') {
                            $userSallary->cut_amount += $m;
                            $userSallary->update();
                        }
                    }

                    if ($request->select_type == 'decrement') {
                        $m = -$m;
                    }
                    if ($m > 0) {
                        SalaryTrx::query()->create(
                            [
                                'type' => 1,
                                'oid' => $agency->id,
                                'amount' => $m,
                                't_no' => rand(11111111, 99999999),
                                'note' => 'paid via admin',
                                'payer_id' => auth()->id(),
                                'payer_type' => 0
                            ]
                        );
                    }
                }
            } elseif (\request('id') && $type == 'user') {
                $user = User::query()->find(\request('id'));
                if ($user) {
                    if ($request->select_type == 'decrement') {
                        if ($user->salary < $amount) {
                            return Common::apiResponse(false, __('low balance'));
                        }
                    }
                    $m = $amount ?: $user->salary;
                    if ($m > 0) {
                        $userSallary = UserSallary::where('user_id', \request('id'))->latest('created_at')->first();
                        if ($userSallary == null) {
                            $userSallary = new UserSallary();
                            $userSallary->user_id = \request('id');
                            $userSallary->hours = "0 / 0";
                            $userSallary->days = "0 / 0";
                            $userSallary->sallary = 0;
                            $userSallary->agency_sallary = 0;
                            if ($request->select_type == 'increment') {
                                $userSallary->cut_amount = -$m;
                            }
                            if ($request->select_type == 'decrement') {
                                $userSallary->cut_amount = $m;
                            }
                            $userSallary->month = now()->month;
                            $userSallary->year = now()->year;
                            $userSallary->is_paid = 0;
                            $userSallary->save();
                        } else {
                            if ($request->select_type == 'increment') {
                                $userSallary->cut_amount -= $m;
                                $userSallary->save();
                            }
                            if ($request->select_type == 'decrement') {
                                $userSallary->cut_amount += $m;
                                $userSallary->save();
                            }
                        }

                        // if ($request->select_type == 'decrement') {
                        //     $amount = -$amount;
                        // }
                        SalaryTrx::query()->create(
                            [
                                'type' => 0,
                                'oid' => $user->id,
                                'amount' => ($amount ?: $user->salary),
                                't_no' => rand(11111111, 99999999),
                                'note' => 'paid via admin',
                                'payer_id' => auth()->id(),
                                'payer_type' => 0
                            ]
                        );
                    }
                }
            }


            DB::commit();
        } catch (\Exception $exception) {

            DB::rollBack();
            return Common::apiResponse(false, $exception->getMessage());
        }

        return Common::apiResponse(true, 'Success');
    }
    private function getUsersGrid()
    {
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        return User::with('agency:id,name')
            ->when($search, function ($q) use ($search) {
                $q->where('uuid', $search);
            })
            ->paginate($perPage) // Paginate by 10 items per page
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'agency' => $user->agency?->name,
                    'old_usd' => $user->old_usd,
                    'target_usd' => $user->target_usd,
                    'target_token_usd' => $user->target_token_usd,
                    'due' => $user->old_usd + $user->target_usd - $user->target_token_usd,
                ];
            });
    }

    private function getAgenciesGrid()
    {
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        return Agency::select('id', 'name', 'phone', 'old_usd', 'target_usd', 'target_token_usd')
            ->withCount('users')
            ->when($search, function ($q) use ($search) {
                $q->where('id', $search);
            })
            ->paginate($perPage) // Paginate by 10 items per page
            ->through(function ($agency) {
                return [
                    'id' => $agency->id,
                    'name' => $agency->name,
                    'phone' => $agency->phone,
                    'old_usd' => $agency->old_usd,
                    'target_usd' => $agency->target_usd,
                    'target_token_usd' => $agency->target_token_usd,
                    'due' => $agency->old_usd + $agency->target_usd - $agency->target_token_usd,
                    'users_count' => $agency->users_count,
                ];
            });
    }
}

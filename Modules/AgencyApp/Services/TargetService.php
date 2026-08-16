<?php

namespace Modules\AgencyApp\Services;

use App\Helpers\Common;
use App\Models\LiveTime;
use App\Models\Target;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\UserTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TargetService
{


    public function __construct(private User $user) {}


    public function calculateTarget()
    {
        $user           = $this->user;
        $month_received = $user->monthly_diamond_received;
        /*     $agency=Agency::find($user->agency_id);
             if ($this->userTargetType == TargetType::FIXED) {
                 if ($agency->users->where("type_user",1)->sum("monthly_diamond_received") >= $agency->monthly_target) {
                     $user = $this->calculateFixedTarget($month_received, $user);
                 }
             } else {*/
        $user = $this->calculateRegularTarget($month_received, $user);

        $user->salary_is_updated = false;
        //        }
        $user->save();
    }

    /**
     * @param $month_received
     * @param User $user
     * @return User
     */
    public function calculateRegularTarget($month_received, User $user): User
    {
        if ($month_received < 1) {
            $user->monthly_diamond_received = 0;
        }

        if ($user->agency_id != 0 && @$user->type_user != 3) {
            $target = $this->getTarget($month_received);

            if ($target) {
                $hours = 0;
                $days  = 0;
                $times = $this->getUserLiveTime($user);
                if ($times) {
                    $hours = $times->hnum;
                    $days  = $user->monthly_days;
                }

                $t                = $this->calculateUsdFromTarget($target, $hours ?? 0, $days);
                $ap               = $target->agency_share / 100;
                $user->target_usd = $t;

                $this->updateSalaries($user, $t, $ap, $hours, $target, $days, $month_received);
            } else {

                $values = [

                    'diamond'  => $month_received . ' / ' . 0,
                    'sallary' =>  0

                ];


                UserSallary::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'month' => Carbon::now()->month,
                    'year' => Carbon::now()->year,
                    'user_agency_id' => $user->agency_id,
                ], $values);
            }
        }
        return $user;
    }

    public function getTarget(int $diamond): Model|null
    {
        return Target::query()->where('diamonds', '<=', $diamond)->orderBy('diamonds', 'desc')->first();
    }

    /**
     * @param User $user
     * @returns ['uid', 'hnum', 'dnum'] or null
     * @return Model | null
     */
    public function getUserLiveTime(User $user): null|Model
    {
        return LiveTime::query()->where('uid', $user->id)->whereYear('created_at', '=', Carbon::now()->year)->whereMonth('created_at', '=', Carbon::now()->month)->selectRaw('uid, sum(hours) as hnum, count(days) as dnum')->groupBy('uid')->first();
    }

    public function calculateUsdFromTarget(Model $target, float $hours, int $days): float
    {
        // $per = 0.50;
        $per = common::getDiamondsPercentage();

        if ($target->hours <= $hours) {
            $per += 0.20;
        }
        if ($target->days <= $days) {
            $per += 0.30;
        }
        if (Common::getConf('all_target_or_nothing') == 'true') {
            if ($per < 1) {
                $per = 0;
            }
        }
        $usd = Common::getTargetUsd($target->diamonds, $target->agency_share);
        return $usd * $per;
    }

    private function updateSalaries(User &$user, $t, $ap, $hours, $target, $days, $month_received, array $extra = null): void
    {
        try {
            $values = [
                'user_id'             => $user->id,
                'add_month' => Carbon::now()->month,
                'add_year'            => Carbon::now()->year,
                'agency_id'           => $user->agency_id,
                'target_id' => @$target->id,
                'target_diamonds'     => @$target->diamonds ?? 0,
                'target_usd' => @$target->usd ?? 0,
                'target_hours'        => @$target->hours ?? 0,
                'target_days'         => @$target->days ?? 0,
                'target_agency_share' => @$target->agency_share ?? 0,
                'user_diamonds'       => $month_received,
                'user_hours'          => $hours,
                'user_days'           => $days,
            ];
            if (0.0 <= $t) {
                $values['user_obtain']   = $t;
                $values['agency_obtain'] = $t * $ap;
            }
            UserTarget::query()->updateOrCreate([
                'user_id' => $user->id,
                'add_month' => Carbon::now()->month,
                'add_year' => Carbon::now()->year,
            ], $values);
        } catch (\Exception $e) {
        }

        $values = [
            'agency_sallary' => $t * $ap,
            'hours'          => $hours . ' / ' . (@$target->hours ?? 0),
            'days'           => $days . ' / ' . ($target->days ?? 0),
            'extras'         => $extra !== null ? json_encode($extra) : null,
            'diamond'        => $month_received . ' / ' . @$target->diamonds ?? 0

        ];
        if (0 < $t) $values['sallary'] = $t;

        $userSalary = UserSallary::query()->where([
            'user_id' => $user->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'user_agency_id' => $user->agency_id,
        ])->orderByDesc('id')->lock()->first();
        if ($userSalary) {
            $userSalary->update($values);
        } else {
            UserSallary::query()->create([
                'user_id' => $user->id,
                'month' => Carbon::now()->month,
                'year' => Carbon::now()->year,
                'user_agency_id' => $user->agency_id,
                ...$values
            ])->lock();
            //            UserSallary::query()->where([
            //                                            'user_id' => $user->id,
            //                                            'month' => Carbon::now()->month,
            //                                            'year' => Carbon::now()->year,
            //                                            'user_agency_id' => $user->agency_id,
            //
            //                                        ])->where('id','!=', $userSalary->id)->delete();

        }
    }
}

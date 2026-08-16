<?php

namespace App\Observers;

use App\Facades\UserHandling;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\MonthlyDiamondReceive;
use App\Models\User;
use App\Models\UsersJoinedAgency;
use Illuminate\Support\Facades\Auth;
use Modules\Milestones\Helpers\MilestoneHelper;

class AgencyObserver
{
    /**
     * When true, the ownership-transfer branch of updated() is skipped because
     * the caller (Admin\AgencyController) already performs the full transfer
     * inside its own locked transaction. Scoped per-request/action and always
     * reset in a finally block by the caller.
     */
    public static bool $skipOwnershipTransfer = false;

    /**
     * Handle the Agency "created" event.
     *
     * @return void
     */
    public function created(Agency $agency)
    {
        $updateData = [
            'agency_id' => $agency->id,
        ];
        User::query()->where('id', $agency->app_owner_id)->update($updateData);

        $updateDataMonth['monthly_diamond_received'] = 0;
        $userDiamond = MonthlyDiamondReceive::query()->where('user_id', $agency->app_owner_id)->where('month', now()->month)->where('year', now()->year)->first();
        if ($userDiamond) $userDiamond->update($updateDataMonth);
        $user = User::find($agency->app_owner_id);

        MilestoneHelper::grantMilestoneToUser($user, 'host-agency-owner');
        $exists = UsersJoinedAgency::where([
            'user_id' =>  $user->id,
            'agency_id' => $agency->id,
            'type' => 1,
        ])->whereNull('leave_date')->exists();

        if (!$exists) {
            UsersJoinedAgency::create([
                'user_id' => $user->id,
                'agency_id' => $agency->id,
                'type' => 1,
                'join_date' => now(),
                'status' => 'Joined',
            ]);
        }

        info('create host-agency-owner milestone');
    }

    /**
     * Handle the Agency "updated" event.
     *
     * @return void
     */
    public function updated(Agency $agency)
    {
        if (!$agency->wasChanged('app_owner_id')) {
            return;
        }

        // Admin\AgencyController performs the full ownership transfer (old-owner
        // detach + new-owner assignment + join rows + milestone) inside its own
        // locked transaction, so skip this branch to avoid doing it twice.
        // Other write paths (Bd, Tik, SalaryTransaction, AgencyApp) do NOT set
        // this flag and still rely on this branch as their transfer source.
        if (self::$skipOwnershipTransfer) {
            return;
        }

        $originalOwnerId = $agency->getOriginal('app_owner_id');
        $newOwnerId = $agency->app_owner_id;

        if ($originalOwnerId) {
            $oldOwner = User::find($originalOwnerId);
            if ($oldOwner) {
                UserHandling::changeUserAgency($oldOwner, 0, 0);
                $oldOwner->is_host = 0;
                $oldOwner->save();

                MilestoneHelper::removeReward($oldOwner, 'host-agency-owner');
                $this->updatePreviousAgencyJoined($oldOwner, $agency->id);
                info('remove host-agency-owner milestone from old owner');
            }
        }

        if ($newOwnerId) {
            $newUser = User::find($newOwnerId);
            if ($newUser) {
                UserHandling::changeUserAgency($newUser, $agency->id, 2);
                $newUser->is_host = 1;
                $newUser->save();

                MilestoneHelper::grantMilestoneToUser($newUser, 'host-agency-owner');

                $exists = UsersJoinedAgency::where([
                    'user_id' =>  $newUser->id,
                    'agency_id' => $agency->id,
                    'type' => 1,
                ])->whereNull('leave_date')->exists();

                if (!$exists) {
                    UsersJoinedAgency::create([
                        'user_id' => $newUser->id,
                        'agency_id' => $agency->id,
                        'type' => 1,
                        'join_date' => now(),
                        'status' => 'Joined',
                    ]);
                }
                info('grant host-agency-owner milestone to new owner');
            }
        }

        User::query()->where('id', $agency->app_owner_id)->update(['agency_id' => $agency->id]);
    }

    /**
     * Handle the Agency "deleted" event.
     *
     * @return void
     */
    public function deleted(Agency $agency)
    {
        //            if ($agency->Host_agency) {
        AgencyJoinRequest::query()->where('agency_id', $agency->id)->delete();

        $owner = User::find($agency->app_owner_id);
        if ($owner) {
            // Use centralized method to remove owner from agency
            UserHandling::changeUserAgency($owner, 0, 0);
        }

        UserHandling::kickOfAllUsersFromAgency($agency);
        User::query()->where('agency_id', $agency->id)->update(['agency_id' => 0, 'type_user' => 0]);
        $joinedAgency = UsersJoinedAgency::where(['agency_id' => $agency->id])->get();
        if ($joinedAgency) UsersJoinedAgency::where('agency_id', $agency->id)->update(['leave_date' => now(), 'status' => 'delete agency from admin']);

        if ($owner) {
            Admin::where('username', $owner->uuid)->delete();
            MilestoneHelper::removeReward($owner, 'host-agency-owner');
        }
        info('delete host-agency-owner milestone');
        //            }
    }


    private function updatePreviousAgencyJoined(User $user, $agencyId)
    {
        $checkAgencyUser = UsersJoinedAgency::where([
            'user_id' => $user->id,
            'agency_id' => $agencyId,
        ])->whereNull('leave_date')->first();

        if (!$checkAgencyUser) {
            UsersJoinedAgency::create([
                'user_id' => $user->id,
                'agency_id' => $agencyId,
                'type' => 2,
                'join_date' => now(),
                'leave_date' => now(),
                'status' => 'change agency by admin',
                'kicked_by_admin' => Auth::id(),
            ]);
        } else {
            $checkAgencyUser->update([
                'leave_date' => now(),
                'status' => 'change agency by admin',
                'kicked_by_admin' => Auth::id()
            ]);
        }
    }

}

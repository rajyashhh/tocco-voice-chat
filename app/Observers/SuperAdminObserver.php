<?php

namespace App\Observers;

use App\Models\User;
use Modules\Milestones\Helpers\MilestoneHelper;
use Modules\Country\Entities\SuperAdmin;


class SuperAdminObserver
{

    public function created(SuperAdmin $superAdmin)
    {
        $userAppId = $superAdmin->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_super_admin = 1;
            $userApp->save();
            MilestoneHelper::grantMilestoneToUser($userApp->id, 'country');
            info('create super-admin milestone');
        }
    }


    public function updated(SuperAdmin $superAdmin)
    {
        if (!$superAdmin->wasChanged('app_id')) {
            return;
        }

        $originalAppId = $superAdmin->getOriginal('app_id');
        $newAppId = $superAdmin->app_id;

        if ($originalAppId && $originalAppId != $newAppId) {
            $oldUser = User::find($originalAppId);

            if ($oldUser) {
                $oldUser->update(['is_super_admin' => 0]);
                MilestoneHelper::removeReward($oldUser, 'country');
                info('update super-admin milestone');
            }
        }

        if ($newAppId) {
            $newUser = User::find($newAppId);

            if ($newUser) {
                $newUser->update(['is_super_admin' => 1]);
                MilestoneHelper::grantMilestoneToUser($newUser, 'country');
                info('update 2 super-admin milestone');
            }
        }
    }

    /**
     * Handle the Agency "deleted" event.
     *
     * @return void
     */
    public function deleted(SuperAdmin $superAdmin)
    {

        $userAppId = $superAdmin->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_super_admin = 0;
            $userApp->save();
            MilestoneHelper::removeReward($userApp, 'country');
            info('delete super-admin milestone');
        }
    }
}

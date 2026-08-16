<?php

namespace App\Observers;

use App\Models\User;
use Modules\Region\Entities\AreaManager;
use Modules\Milestones\Helpers\MilestoneHelper;


class AreaManagerObserver
{

    public function created(AreaManager $areaManager)
    {
        $userAppId = $areaManager->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_area_manager = 1;
            $userApp->save();

            MilestoneHelper::grantMilestoneToUser($userApp->id, 'region');
        }
    }


    public function updated(AreaManager $areaManager)
    {
        if (!$areaManager->wasChanged('app_id')) {
            return;
        }

        $originalAppId = $areaManager->getOriginal('app_id');
        $newAppId = $areaManager->app_id;

        if ($originalAppId) {
            $oldUser = User::find($originalAppId);
            if ($oldUser) {
                $oldUser->update(['is_area_manager' => 0]);
                MilestoneHelper::removeReward($oldUser, 'region');
            }

            if ($newAppId) {
                $newUser = User::find($newAppId);

                if ($newUser) {
                    $newUser->update(['is_area_manager' => 1]);
                    MilestoneHelper::grantMilestoneToUser($newUser->id, 'region');
                }
            }
        }
    }

    /**
     * Handle the Agency "deleted" event.
     *
     * @return void
     */
    public function deleted(AreaManager $areaManager)
    {
        $userAppId = $areaManager->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_area_manager = 0;
            $userApp->save();
            MilestoneHelper::removeReward($userApp, 'region');
        }
    }
}

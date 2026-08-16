<?php

namespace App\Observers;

use App\Models\Bd;
use App\Models\User;
use Modules\Milestones\Helpers\MilestoneHelper;


class BdObserver
{

    public function created(Bd $bd)
    {
        $userAppId = $bd->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_bd = 1;
            $userApp->save();
            MilestoneHelper::grantMilestoneToUser($userApp, 'bd');
            info('create bd milestone');
        }
    }


    public function updated(Bd $bd)
    {
        if (!$bd->wasChanged('app_id')) {
            return;
        }

        $originalAppId = $bd->getOriginal('app_id');
        $newAppId = $bd->app_id;

        if ($newAppId === null) {
            return;
        }

        if ($originalAppId && $originalAppId != $newAppId) {
            $oldUser = User::find($originalAppId);

            if ($oldUser) {
                $oldUser->update(['is_bd' => 0]);
                MilestoneHelper::removeReward($oldUser, 'bd');
                info('update bd milestone');
            }
        }

        $newUser = User::find($newAppId);
        if ($newUser) {
            $newUser->update(['is_bd' => 1]);
            MilestoneHelper::grantMilestoneToUser($newUser, 'bd');
            info('update 2 bd milestone');
        }
    }

    /**
     * Handle the Agency "deleted" event.
     *
     * @return void
     */
    public function deleted(Bd $bd)
    {
        $userAppId = $bd->app_id;
        $userApp = User::find($userAppId);
        if (isset($userApp)) {
            $userApp->is_bd = 0;
            $userApp->save();
            MilestoneHelper::removeReward($userApp, 'bd');
            info('delete bd milestone');
        }
    }
}

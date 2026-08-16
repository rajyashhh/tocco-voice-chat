<?php

namespace App\Observers;


use App\Models\ShippingAgency;
use App\Models\User;

use Modules\Milestones\Helpers\MilestoneHelper;

class ShippingAgencyObserver
{
    /**
     * Handle the Agency "created" event.
     *
     * @return void
     */
    public function created(ShippingAgency $agency)
    {
        $user = User::find($agency->app_owner_id);
        if ($user) MilestoneHelper::grantMilestoneToUser($user, 'charge-agency-owner');
        info('create charge-agency-owner milestone');
    }

    /**
     * Handle the Agency "updated" event.
     *
     * @return void
     */
    public function updated(ShippingAgency $agency)
    {
        if (!$agency->wasChanged('app_owner_id')) {
            return;
        }

        $originalOwnerId = $agency->getOriginal('app_owner_id');
        $newOwnerId = $agency->app_owner_id;

        if ($originalOwnerId) {
            $oldOwner = User::find($originalOwnerId);
            if ($oldOwner) {
                MilestoneHelper::removeReward($oldOwner, 'charge-agency-owner');
                info('remove charge-agency-owner milestone from old owner');
            }
        }

        if ($newOwnerId) {
            $newUser = User::find($newOwnerId);
            if ($newUser) {
                MilestoneHelper::grantMilestoneToUser($newUser, 'charge-agency-owner');
                info('grant charge-agency-owner milestone to new owner');
            }
        }
    }

    /**
     * Handle the Agency "deleted" event.
     *
     * @return void
     */
    public function deleted(ShippingAgency $agency)
    {
        $user = User::find($agency->app_owner_id);
        if ($user) MilestoneHelper::removeReward($user, 'charge-agency-owner');
        info('delete charge-agency-owner milestone');
    }
}

<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Family;
use App\Models\FamilyUser;
use Modules\Milestones\Helpers\MilestoneHelper;

class FamilyObserver
{
    /**
     * Handle the Family "deleted" event.
     *
     * @return void
     */
    public function deleted(Family $family)
    {
        $owner = User::find($family->user_id);
        MilestoneHelper::removeReward($owner, 'family-owner');
        info('create family-owner milestone');
        User::query()->where('family_id', $family->id)->update(['family_id' => null]);
        FamilyUser::query()->where('family_id', $family->id)->delete();
    }
}

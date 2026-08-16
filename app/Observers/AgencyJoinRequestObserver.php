<?php

namespace App\Observers;

use App\Models\AgencyJoinRequest;
use App\Models\User;
use Modules\Milestones\Helpers\MilestoneHelper;

class AgencyJoinRequestObserver
{
    public function updating(AgencyJoinRequest $agencyJoinRequest)
    {
        if ($agencyJoinRequest->status == 1) {
            $user = User::query()->find($agencyJoinRequest->user_id);
            if ($user) {
                \App\Facades\UserHandling::changeUserAgency($user, $agencyJoinRequest->agency_id, 1);

                $agid = $user->agency_id;
                MilestoneHelper::grantMilestoneToUser($user, 'host');
                if ($agid == '' || $agid == null || $agid == 0) {
                    $user->coins = 0;
                    $user->save();
                    uploadMonthlyDiamondReceive($user->id, 0);
                }
            }
        }
    }

}
